<?php

declare(strict_types=1);

namespace Webservis;

use PDO;
use PDOException;
use PDOStatement;
use Closure;

/**
 * Class Database
 *
 * @author Sanan Mammadov
 * @web http://www.webservis.az
 * @mail sanan@webservis.az
 * @date 13 April 2017
 * @update 20 February 2026
 * @php 8.2+
 */
class Database extends PDO
{
    // Readonly properties - konstruktorda bir dəfə təyin olunur
    private readonly string $dbName;
    private readonly bool $autolog;

    // Nullable typed properties
    private ?string $type = null;
    private ?string $sql = null;
    private ?string $unionSql = null;
    private ?string $tableName = null;
    private ?array $where = null;
    private ?array $having = null;
    private bool $grouped = false;
    private ?int $group_id = null;
    private ?array $join = null;
    private ?string $orderBy = null;
    private ?string $groupBy = null;
    private ?string $limit = null;
    private ?int $page = null;
    private ?int $totalRecord = null;
    private ?int $paginationLimit = null;
    private ?string $html = null;
    private ?string $autolog_type = null;
    private ?string $autolog_tableName = null;
    private ?int $lastInsertedId = null;

    // Public typed properties
    public ?int $pageCount = null;
    public bool $debug = false;
    public string $paginationItem = '<li><a class="page btn btn-outline-primary ms-1 fs-1 [active]" href="[url]">[text]</a></li>';

    /** @var array<string> */
    public array $reference = ['NOW()'];

    /**
     * Database constructor
     */
    public function __construct(
        string $host,
        string $dbname,
        string $username,
        string $password,
        string $charset = 'utf8mb4',
        bool $autolog = false
    ) {
        try {
            parent::__construct(
                dsn: "mysql:host={$host};dbname={$dbname};charset={$charset}",
                username: $username,
                password: $password,
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                ]
            );

            $this->dbName = $dbname;
            $this->autolog = $autolog;

            if ($this->autolog) {
                $this->createLogTable();
            }
        } catch (PDOException $e) {
            $this->showError($e);
        }
    }

    /**
     * FROM clause
     */
    public function from(string $tableName): static
    {
        $this->sql = "SELECT * FROM {$tableName}";
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * SELECT columns
     */
    public function select(string $columns): static
    {
        $this->sql = str_replace(' * ', " {$columns} ", $this->sql ?? '');
        return $this;
    }

    /**
     * UNION query
     */
    public function union(): static
    {
        $this->type = 'union';
        $this->unionSql = $this->sql;
        return $this;
    }

    /**
     * Group conditions with closure
     */
    public function group(Closure $fn): static
    {
        static $group_id = 0;
        $this->grouped = true;
        $fn($this);
        $this->group_id = ++$group_id;
        $this->grouped = false;
        return $this;
    }

    /**
     * WHERE condition
     */
    public function where(
        string $column,
        mixed $value = '',
        string $mark = '=',
        string $logical = '&&'
    ): static {
        if ($column) {
            $this->where[] = [
                'column' => $column,
                'value' => $value,
                'mark' => $mark,
                'logical' => $logical,
                'grouped' => $this->grouped,
                'group_id' => $this->group_id
            ];
        }
        return $this;
    }

    /**
     * Multiple WHERE conditions
     *
     * @param array<array> $wheres
     */
    public function wheres(array $wheres = []): static
    {
        foreach ($wheres as $where) {
            $this->where(
                column: $where[0],
                value: $where[1] ?? '',
                mark: $where[2] ?? '=',
                logical: $where[3] ?? '&&'
            );
        }
        return $this;
    }

    /**
     * HAVING condition
     */
    public function having(
        string $column,
        mixed $value = '',
        string $mark = '=',
        string $logical = '&&'
    ): static {
        $this->having[] = [
            'column' => $column,
            'value' => $value,
            'mark' => $mark,
            'logical' => $logical,
            'grouped' => $this->grouped,
            'group_id' => $this->group_id
        ];
        return $this;
    }

    /**
     * OR WHERE condition
     */
    public function or_where(string $column, mixed $value, string $mark = '='): static
    {
        return $this->where($column, $value, $mark, '||');
    }

    /**
     * OR HAVING condition
     */
    public function or_having(string $column, mixed $value, string $mark = '='): static
    {
        return $this->having($column, $value, $mark, '||');
    }

    /**
     * Status filter shorthand
     */
    public function status(mixed $value = '', string $mark = '=', string $logical = '&&'): static
    {
        return $this->where('status', $value, $mark, $logical);
    }

    /**
     * ID filter shorthand
     */
    public function id(mixed $value = '', string $mark = '=', string $logical = '&&'): static
    {
        return $this->where('id', $value, $mark, $logical);
    }

    /**
     * JOIN clause
     */
    public function join(string $targetTable, string $joinSql, string $joinType = 'inner'): static
    {
        $joinTypeUpper = strtoupper($joinType);
        $formattedSql = sprintf($joinSql, $targetTable, $this->tableName);
        $this->join[] = " {$joinTypeUpper} JOIN {$targetTable} ON {$formattedSql}";
        return $this;
    }

    /**
     * LEFT JOIN
     */
    public function leftJoin(string $targetTable, string $joinSql): static
    {
        return $this->join($targetTable, $joinSql, 'left');
    }

    /**
     * RIGHT JOIN
     */
    public function rightJoin(string $targetTable, string $joinSql): static
    {
        return $this->join($targetTable, $joinSql, 'right');
    }

    /**
     * ORDER BY clause
     */
    public function orderBy(string $columnName, string $sort = 'ASC'): static
    {
        $this->orderBy = " ORDER BY {$columnName} {$sort}";
        return $this;
    }

    /**
     * ORDER BY RAND()
     */
    public function orderByRand(): static
    {
        $this->orderBy = ' ORDER BY RAND()';
        return $this;
    }

    /**
     * Dual ORDER BY
     */
    public function dual_orderby(
        string $columnName1,
        string $columnName2,
        string $sort1 = 'ASC',
        string $sort2 = 'ASC'
    ): static {
        $sort1 = strtoupper($sort1);
        $sort2 = strtoupper($sort2);
        $this->orderBy = " ORDER BY {$columnName1} {$sort1}, {$columnName2} {$sort2}";
        return $this;
    }

    /**
     * Custom ORDER BY
     */
    public function custom_orderby(string $o_sql): static
    {
        $this->orderBy = " {$o_sql}";
        return $this;
    }

    /**
     * GROUP BY clause
     */
    public function groupBy(string $columnName): static
    {
        $this->groupBy = " GROUP BY {$columnName}";
        return $this;
    }

    /**
     * LIMIT clause
     */
    public function limit(int $start, int $limit): static
    {
        $this->limit = " LIMIT {$start},{$limit}";
        return $this;
    }

    /**
     * Fetch all results
     *
     * @return array<array<string, mixed>>
     */
    public function all(): array
    {
        try {
            $query = $this->generateQuery();
            return $query?->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            $this->showError($e);
            return [];
        }
    }

    /**
     * Fetch first result
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        try {
            $query = $this->generateQuery();
            $result = $query?->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->showError($e);
            return null;
        }
    }

    /**
     * Generate and execute query
     */
    public function generateQuery(): ?PDOStatement
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }

        $this->get_where('where');

        if ($this->groupBy) {
            $this->sql .= $this->groupBy;
            $this->groupBy = null;
        }

        $this->get_where('having');

        if ($this->orderBy) {
            $this->sql .= $this->orderBy;
            $this->orderBy = null;
        }

        if ($this->limit) {
            $this->sql .= $this->limit;
            $this->limit = null;
        }

        if ($this->type === 'union') {
            $this->sql = "{$this->unionSql} UNION ALL {$this->sql}";
        }

        if ($this->debug) {
            echo $this->getSqlString();
        }

        $this->type = null;

        return $this->query($this->sql) ?: null;
    }

    /**
     * Build WHERE/HAVING clause
     */
    private function get_where(string $conditionType = 'where'): void
    {
        $conditions = $this->{$conditionType};

        if (!is_array($conditions) || count($conditions) === 0) {
            return;
        }

        $keyword = $conditionType === 'having' ? 'HAVING' : 'WHERE';
        $whereClause = " {$keyword} ";

        foreach ($conditions as $key => $item) {
            // Group opening
            if (
                $item['grouped'] === true &&
                (
                    (!isset($conditions[$key - 1])) ||
                    ($conditions[$key - 1]['grouped'] !== true) ||
                    ($conditions[$key - 1]['group_id'] !== $item['group_id'])
                )
            ) {
                $logical = isset($conditions[$key - 1]) && $conditions[$key - 1]['grouped'] === true
                    ? " {$item['logical']}"
                    : '';
                $whereClause .= "{$logical} (";
            }

            // Build condition using match expression (PHP 8.0+)
            $where = match ($item['mark']) {
                'LIKE' => "{$item['column']} LIKE \"%{$item['value']}%\"",
                'NOT LIKE' => "{$item['column']} NOT LIKE \"%{$item['value']}%\"",
                'BETWEEN' => "{$item['column']} BETWEEN \"{$item['value'][0]}\" AND \"{$item['value'][1]}\"",
                'NOT BETWEEN' => "{$item['column']} NOT BETWEEN \"{$item['value'][0]}\" AND \"{$item['value'][1]}\"",
                'FIND_IN_SET' => "FIND_IN_SET({$item['column']}, {$item['value']})",
                'FIND_IN_SET_REVERSE' => "FIND_IN_SET({$item['value']}, {$item['column']})",
                'IN' => $item['column'] . ' IN(' . (is_array($item['value']) ? implode(', ', $item['value']) : $item['value']) . ')',
                'NOT IN' => $item['column'] . ' NOT IN(' . (is_array($item['value']) ? implode(', ', $item['value']) : $item['value']) . ')',
                'SOUNDEX' => "SOUNDEX({$item['column']}) LIKE CONCAT('%', TRIM(TRAILING '0' FROM SOUNDEX('{$item['value']}')), '%')",
                'CUSTOM' => $item['column'],
                default => $this->buildDefaultCondition($item)
            };

            // Append to clause
            if ($key === 0) {
                $whereClause .= ($item['grouped'] === false && ($conditions[$key + 1]['grouped'] ?? false) === true)
                    ? "{$where} {$item['logical']}"
                    : $where;
            } else {
                $whereClause .= " {$item['logical']} {$where}";
            }

            // Group closing
            if (
                $item['grouped'] === true &&
                (
                    (!isset($conditions[$key + 1])) ||
                    ($conditions[$key + 1]['grouped'] !== true) ||
                    ($conditions[$key + 1]['group_id'] !== $item['group_id'])
                )
            ) {
                $whereClause .= ' )';
            }
        }

        $whereClause = rtrim($whereClause, '||');
        $whereClause = rtrim($whereClause, '&&');
        $whereClause = preg_replace('/\(\s+(\|\||&&)/', '(', $whereClause);
        $whereClause = preg_replace('/(\|\||&&)\s+\)/', ')', $whereClause);

        $this->sql .= $whereClause;
        $this->unionSql .= $whereClause;
        $this->{$conditionType} = null;
    }

    /**
     * Build default condition
     */
    private function buildDefaultCondition(array $item): string
    {
        $value = trim((string)$item['value']);
        $isReference = !empty(preg_grep("/{$value}/i", $this->reference));
        $quotedValue = $isReference ? $item['value'] : "\"{$item['value']}\"";

        return "{$item['column']} {$item['mark']} {$quotedValue}";
    }

    /**
     * INSERT query
     */
    public function insert(string $tableName): static
    {
        $this->sql = "INSERT INTO {$tableName}";
        $this->tableName = $tableName;
        $this->autolog_tableName = $tableName;
        $this->autolog_type = 'insert';
        return $this;
    }

    /**
     * SET data for INSERT/UPDATE
     */
    public function set(array|string $data, mixed $value = null): bool
    {
        try {
            $executeValue = null;

            if ($value !== null && is_string($data)) {
                if (str_contains($value, '+') || str_contains($value, '-')) {
                    $this->sql .= " SET {$data} = {$data} {$value}";
                } else {
                    $this->sql .= " SET {$data} = :{$data}";
                    $executeValue = [$data => $value];
                }
            } else {
                $sets = implode(', ', array_map(
                    fn(string $item): string => "{$item} = :{$item}",
                    array_keys($data)
                ));
                $this->sql .= " SET {$sets}";
                $executeValue = $data;
            }

            $this->get_where('where');
            $this->get_where('having');

            $query = $this->prepare($this->sql);
            $result = $query->execute($executeValue);

            // Save lastInsertId before logging
            if ($this->autolog_type === 'insert') {
                $this->lastInsertedId = (int)parent::lastInsertId();
            }

            $this->logAction(
                $this->autolog_tableName,
                $this->autolog_type,
                is_array($data) ? $data : [$data => $value]
            );

            return $result;
        } catch (PDOException $e) {
            $this->showError($e);
            return false;
        }
    }

    /**
     * Increment/Decrement column value
     */
    public function incrementDecrement(string $column, string $value = '+ 1'): bool
    {
        try {
            $this->sql .= " SET {$column} = {$column} {$value}";

            $this->get_where('where');
            $this->get_where('having');

            $query = $this->prepare($this->sql);
            return $query->execute();
        } catch (PDOException $e) {
            $this->showError($e);
            return false;
        }
    }

    /**
     * Get last inserted ID
     */
    public function lastId(): int
    {
        if ($this->lastInsertedId !== null) {
            $id = $this->lastInsertedId;
            $this->lastInsertedId = null;
            return $id;
        }
        return (int)parent::lastInsertId();
    }

    /**
     * UPDATE query
     */
    public function update(string $tableName): static
    {
        $this->sql = "UPDATE {$tableName}";
        $this->tableName = $tableName;
        $this->autolog_tableName = $tableName;
        $this->autolog_type = 'update';
        return $this;
    }

    /**
     * DELETE query
     */
    public function delete(string $tableName): static
    {
        $this->sql = "DELETE FROM {$tableName}";
        $this->tableName = $tableName;
        $this->autolog_tableName = $tableName;
        $this->autolog_type = 'delete';
        return $this;
    }

    /**
     * Execute DELETE/UPDATE without SET
     */
    public function done(): int|false
    {
        try {
            $this->get_where('where');
            $this->get_where('having');

            $logTableName = $this->autolog_tableName;
            $result = $this->exec($this->sql);

            $this->logAction($logTableName, 'delete', ['sql' => $this->sql]);

            return $result;
        } catch (PDOException $e) {
            $this->showError($e);
            return false;
        }
    }

    /**
     * Get total from aggregate query
     */
    public function total(): int
    {
        if ($this->join) {
            $this->sql .= implode(' ', $this->join);
            $this->join = null;
        }

        $this->get_where('where');

        if ($this->groupBy) {
            $this->sql .= $this->groupBy;
            $this->groupBy = null;
        }

        $this->get_where('having');

        if ($this->orderBy) {
            $this->sql .= $this->orderBy;
            $this->orderBy = null;
        }

        if ($this->limit) {
            $this->sql .= $this->limit;
            $this->limit = null;
        }

        $query = $this->query($this->sql)?->fetch(PDO::FETCH_ASSOC);

        return (int)($query['total'] ?? 0);
    }

    /**
     * COUNT query
     */
    public function count(): int
    {
        $this->select("COUNT({$this->tableName}.id) as total");
        return $this->total();
    }

    /**
     * Setup pagination
     *
     * @return array{start: int, limit: int}
     */
    public function pagination(int $totalRecord, int $paginationLimit, int $page): array
    {
        $this->paginationLimit = $paginationLimit;
        $this->page = $page;
        $this->totalRecord = $totalRecord;
        $this->pageCount = (int)ceil($this->totalRecord / $this->paginationLimit);

        $start = ($this->page * $this->paginationLimit) - $this->paginationLimit;

        return [
            'start' => $start,
            'limit' => $this->paginationLimit
        ];
    }

    /**
     * Generate pagination HTML
     */
    public function showPagination(string $url, string $class = 'active'): ?string
    {
        if ($this->totalRecord === null || $this->paginationLimit === null) {
            return null;
        }

        if ($this->totalRecord <= $this->paginationLimit) {
            return null;
        }

        $this->html = '';

        for ($i = $this->page - 5; $i <= $this->page + 5; $i++) {
            if ($i > 0 && $i <= $this->pageCount) {
                $activeClass = $i === $this->page ? $class : '';
                $pageUrl = str_replace('{{page}}', (string)$i, $url);

                $this->html .= str_replace(
                    ['[active]', '[text]', '[url]'],
                    [$activeClass, (string)$i, $pageUrl],
                    $this->paginationItem
                );
            }
        }

        return $this->html;
    }

    /**
     * Get next page number
     */
    public function nextPage(): int
    {
        return ($this->page + 1 < $this->pageCount)
            ? $this->page + 1
            : $this->pageCount ?? 1;
    }

    /**
     * Get previous page number
     */
    public function prevPage(): int
    {
        return ($this->page - 1 > 0) ? $this->page - 1 : 1;
    }

    /**
     * Get SQL string for debugging
     */
    public function getSqlString(): string
    {
        $this->get_where('where');
        $this->get_where('having');
        return $this->errorTemplate($this->sql ?? '', self::class . ' SQL Sorgusu');
    }

    /**
     * Get current SQL
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * BETWEEN condition
     *
     * @param array{0: mixed, 1: mixed} $values
     */
    public function between(string $column, array $values): static
    {
        return $this->where($column, $values, 'BETWEEN');
    }

    /**
     * NOT BETWEEN condition
     *
     * @param array{0: mixed, 1: mixed} $values
     */
    public function notBetween(string $column, array $values): static
    {
        return $this->where($column, $values, 'NOT BETWEEN');
    }

    /**
     * FIND_IN_SET condition
     */
    public function findInSet(string $column, mixed $value): static
    {
        return $this->where($column, $value, 'FIND_IN_SET');
    }

    /**
     * FIND_IN_SET_REVERSE condition
     */
    public function findInSetReverse(string $column, mixed $value): static
    {
        return $this->where($column, $value, 'FIND_IN_SET_REVERSE');
    }

    /**
     * IN condition
     */
    public function in(string $column, array|string $value): static
    {
        return $this->where($column, $value, 'IN');
    }

    /**
     * NOT IN condition
     */
    public function notIn(string $column, array|string $value): static
    {
        return $this->where($column, $value, 'NOT IN');
    }

    /**
     * LIKE condition
     */
    public function like(string $column, string $value): static
    {
        return $this->where($column, $value, 'LIKE');
    }

    /**
     * Azerbaijani LIKE with transliteration
     */
    public function az_like(string $table, string $column, string $value): static
    {
        $replace_chain = "REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE({$table}.{$column},
                                    'ə','e'),
                                'ö','o'),
                            'ü','u'),
                        'ç','c'),
                    'ş','s'),
                'ğ','g'),
            'ı','i'),
        'Ə','e')";

        $column_norm_alias = "{$column}_norm";

        if (preg_match('/SELECT\s+(.*?)\s+FROM/is', $this->sql ?? '', $matches)) {
            $current_select_columns = trim($matches[1]);

            if ($current_select_columns === '*' || $current_select_columns === "{$this->tableName}.*") {
                $current_select_columns = "{$table}.*";
            }

            if (stripos($current_select_columns, $column_norm_alias) === false) {
                $new_select = "SELECT {$current_select_columns}, {$replace_chain} AS {$column_norm_alias} FROM";
                $this->sql = preg_replace('/SELECT\s+(.*?)\s+FROM/is', $new_select, $this->sql, 1);
            }
        } else {
            $this->sql = "SELECT {$table}.*, {$replace_chain} AS {$column_norm_alias} FROM {$this->tableName}";
        }

        $where_sql = "({$table}.{$column} LIKE '%{$value}%' OR {$replace_chain} LIKE '%{$value}%')";

        return $this->custom_where($where_sql);
    }

    /**
     * NOT LIKE condition
     */
    public function notLike(string $column, string $value): static
    {
        return $this->where($column, $value, 'NOT LIKE');
    }

    /**
     * SOUNDEX condition
     */
    public function soundex(string $column, string $value): static
    {
        return $this->where($column, $value, 'SOUNDEX');
    }

    /**
     * Custom WHERE condition
     */
    public function custom_where(string $column, mixed $value = 1): static
    {
        return $this->where($column, $value, 'CUSTOM');
    }

    /**
     * Handle undefined methods
     */
    public function __call(string $name, array $args): never
    {
        throw new \BadMethodCallException(
            "Method '{$name}' not found in " . self::class
        );
    }

    /**
     * Show error message
     */
    private function showError(PDOException $error): void
    {
        $this->errorTemplate($error->getMessage());
    }

    /**
     * Error template
     */
    private function errorTemplate(string $errorMsg, ?string $title = null): string
    {
        $displayTitle = $title ?? self::class . ' Xətası:';

        $html = <<<HTML
        <div class="db-error-msg-content">
            <div class="db-error-title">{$displayTitle}</div>
            <div class="db-error-msg">{$errorMsg}</div>
        </div>
        <style>
            .db-error-msg-content {
                padding: 15px;
                border-left: 5px solid #c00000;
                background: #f8f8f8;
                margin-bottom: 10px;
            }
            .db-error-title {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 16px;
                font-weight: 500;
            }
            .db-error-msg {
                margin-top: 15px;
                font-size: 14px;
                font-family: Consolas, Monaco, Menlo, monospace;
                color: #c00000;
            }
        </style>
        HTML;

        echo $html;
        return $html;
    }

    /**
     * Truncate table
     */
    public function truncate(string $tableName): PDOStatement|false
    {
        return $this->query("TRUNCATE TABLE {$this->dbName}.{$tableName}");
    }

    /**
     * Truncate all tables
     *
     * @param array<string> $dbs
     */
    public function truncateAll(array $dbs = []): void
    {
        if (count($dbs) === 0) {
            $dbs[] = $this->dbName;
        }

        $query = $this->from('INFORMATION_SCHEMA.TABLES')
            ->select('CONCAT("TRUNCATE TABLE `", table_schema, "`.`", TABLE_NAME, "`;") as query, TABLE_NAME as tableName')
            ->in('table_schema', implode(',', $dbs))
            ->all();

        $this->query('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($query as $row) {
            $this->setAutoIncrement($row['tableName']);
            $this->query($row['query']);
        }

        $this->query('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Set table auto increment
     */
    public function setAutoIncrement(string $tableName, int $ai = 1): PDOStatement|false
    {
        return $this->query("ALTER TABLE `{$tableName}` AUTO_INCREMENT = {$ai}");
    }

    /**
     * Build WHERE SQL from array
     *
     * @param array<array> $where
     */
    public function setWhereSql(array $where = []): string
    {
        if (empty($where)) {
            return 'id > "-1"';
        }

        $where_arr = [];
        $lastKey = array_key_last($where);

        foreach ($where as $key => $wh_item) {
            $operator = $wh_item[2] ?? '=';
            $logical = $wh_item[3] ?? '&&';
            $sql = "{$wh_item[0]} {$operator} \"{$wh_item[1]}\"";

            $where_arr[] = ($key === $lastKey) ? $sql : "{$sql} {$logical}";
        }

        return implode(' ', $where_arr);
    }

    /**
     * Log database action
     */
    private function logAction(?string $table, ?string $type, mixed $content): void
    {
        if (!$this->autolog || !$table || !$type) {
            return;
        }

        try {
            $stmt = $this->prepare(
                "INSERT INTO logs (`table_name`, `type`, `content`, `created_at`) 
                 VALUES (:table, :type, :content, NOW())"
            );

            $stmt->execute([
                ':table' => $table,
                ':type' => $type,
                ':content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            ]);
        } catch (PDOException|\JsonException $e) {
            error_log("Database log error: " . $e->getMessage());
        }

        $this->autolog_type = null;
        $this->autolog_tableName = null;
    }

    /**
     * Create logs table if not exists
     */
    private function createLogTable(): void
    {
        $this->exec("
            CREATE TABLE IF NOT EXISTS logs (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                table_name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                content JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_table_name (table_name),
                INDEX idx_type (type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}