create or replace procedure mockfill (in_t_main in varchar, for_date in varchar) as
    t_main varchar(30);
    maxdepth integer := 15;
    t_aux varchar(25);
    col_constraint varchar(100);
	col_constraint_esc varchar(100);
    paramname varchar(20);
    qpart_insert varchar(100);
    qpart_select varchar(300);
    qpart_from varchar(200);
    qpart_where varchar(300);
    querywhole varchar(2000);
    pval varchar(20);
    q_aux varchar(500);
    is_t_aux_exists integer;
    randcolumns varchar(150);
    randvalues varchar(800);
    qpart_date_select varchar(50) :='';
    qpart_date_insert varchar(20) :='';

-------------------------------------------------------------------------------------
----------получаем наименования колонок таблицы и разрешённые значения---------------
-------------------------------------------------------------------------------------
--	По такому запросу возвращаются не только перечисления, но и другие ограничения, и проверить их можно было бы только по формату самой строки, задающей ограничение. Но поле search_condition таблицы user_constraints не текстового типа VARCHAR, а LONG, а на таком типе полей текстовые функции не действуют. По той же причине и сразу не экранируем одинарные кавычки.
--	Напрямую преобразование типов в данном случае не работает. Это отдельная история, вкратце, рекомендуют использовать промежуточную таблицу. Однако, если применить курсор, текстовые функции уже работают.
	
    cursor cols_constraints(tname varchar) is
        SELECT search_condition FROM user_constraints WHERE table_name = tname and Constraint_type = 'C';

begin
--------------------------------------------------------------------------------------
---------------------инициализация, подготовка временной таблицы----------------------
--------------------------------------------------------------------------------------
-- приводим наименование таблицы к единообразному виду, поскольку оно могло придти и в нижнем регистре, и в верхнем. Все имена таблиц хранятся в базе в верхнем регистре.
    select upper(in_t_main) into t_main from dual;
-- понадобится временная таблица, формируем её наименование из основной
    select t_main || '_AUX' into t_aux from dual;
-- проверяем, существует ли уже временная таблица
    execute immediate 'select count(*) as count from user_tables where table_name=''' || t_aux || '''' into is_t_aux_exists;
    if is_t_aux_exists = 0 then
-- если временная таблица не существует, создаём её. временная таблица будет хранить все возможные сочетания значений параметров и значений. без неё не обойтись.
        execute immediate 'create table ' || t_aux || '(pname varchar(20), pval varchar(20))';--global temporary 
    else
-- если временная таблица существует, очищаем её
        execute immediate 'delete from ' || t_aux;
        end if;
		
--------------------------------------------------------------------------------------
-------------------основной цикл - по столбцам, имеющим ограничения-------------------
--------------------------------------------------------------------------------------
    for col_constraint in cols_constraints(t_main) loop
--	проверяем формат, то, что нельзя было сделать из-за формата поля long. не вполне точная регулярка, но выполняет свою функцию.
		continue when not regexp_like(col_constraint.search_condition,'[a-zA-Z0-1] +in *\((''[^'']+''[ ,]*)+\)');
--	выделяем наименование столбца. с точки зрения конечного пользователя, столбец - это параметр товара.
        select substr(col_constraint.search_condition,1,instr(col_constraint.search_condition,' ')-1) into paramname from dual;
--	нужно экранировать одинарные кавычки, чтобы далее не плодить частокол, просто заменим их на двойные.
		select replace(col_constraint.search_condition,'''','"') into col_constraint_esc from dual;
------------------------------первая задача в цикле-----------------------------------
-- формируем переменные для разных частей окончательного запроса вставки данных в основную таблицу. этот запрос будет выполнен вне этого цикла. здесь только идёт формирование разных его частей, из которых он потом соберётся.
--	для части insert into t_main
        select qpart_insert || ', ' || paramname into qpart_insert from dual;
--	для части select
        select qpart_select || ', ' || 't_' || paramname || '.pval as ' || paramname into qpart_select from dual;
--	для части from
        select qpart_from || ', ' || t_aux ||' t_' || paramname into qpart_from from dual;
--	для where
        select qpart_where || ' and t_' || paramname || '.pname=''' || paramname || '''' into qpart_where from dual;

-------------------------------вторая задача в цикле----------------------------------
--	формирование запроса на вставку во вспомогательную таблицу всех возможных значений колонки вместе с наименованием колонки. Этот запрос будет выполнен прямо сейчас.
--	формирование команды множественной вставки с помощью рекурсии.
--	из color in('white','red','black') получается ('color','white'),('color','red'),('color','black'), 
        select 'insert into ' || t_aux || ' (pname,pval) select ''' || paramname || ''', replace(regexp_substr(''' || col_constraint_esc || ''',''"[^"]+"'',1,level),''"'','''') as paramvalue from dual connect by instr(''' || col_constraint_esc || ''','','',1,level-1)>0 and level<=' || maxdepth into q_aux from dual;
		--DBMS_OUTPUT.PUT_LINE(q_aux);
        execute immediate q_aux;
        end loop;
		
--------------------------------------------------------------------------------------
----------------------подготовка других столбцов - даты и числа-----------------------
--------------------------------------------------------------------------------------
--	формируются следующие части для включения в главный запрос
--	числовые поля
	SELECT 
        listagg(column_name,', ') within group (order by column_name) as column_name,
        listagg('round(DBMS_RANDOM.Value(1, 10)) as val' || rownum,', ') within group (order by rownum) as randvalues
    into 
        randcolumns, randvalues
    FROM
        USER_TAB_COLUMNS
    WHERE
        table_name = t_main and
        data_type='NUMBER'
    group by 1;
	
--	тип поля - дата. формируются необходимые части для вставки в главный запрос
    if for_date is not null or for_date !='' then
--	наименование колонки с датой
        select concat(column_name,' ,') into qpart_date_insert from user_tab_columns where table_name=t_main and data_type='DATE';
        qpart_date_select := 'to_date(''' || for_date || ''',''YYYY-MM-DD''), ';
        end if;

--------------------------------------------------------------------------------------
------------------------сборка и выполнение главного запроса--------------------------
--------------------------------------------------------------------------------------
--	только когда все части запроса сформированы, можно очищать основную таблицу.
--	здесь же можно, наоборот, отменить очистку или сделать её выборочно.
--	это важно, так как основная таблица может содержать нужные не-тестовые, реальные данные
	execute immediate 'delete from ' || t_main;
    select 'insert into ' || t_main || ' ( ' || qpart_date_insert || substr(qpart_insert,2) || ', ' || randcolumns || ') select  ' || qpart_date_select || substr(qpart_select,2) || ', ' || randvalues || ' from ' || substr(qpart_from,2) || ' where ' || substr(qpart_where,5) into querywhole from dual;
    DBMS_OUTPUT.PUT_LINE(querywhole);
    execute immediate querywhole;
    --execute immediate 'update ' || maintable || ' set for_date=sysdate';
    commit;
end;
/
