<?php
//namespace Sample;
defined('BASEPATH') or exit('No direct script access allowed');
require_once 'BaseModel.php';

define('DSN', 'pgsql:host=%s;dbname=%s;');

/**
 * PDO psql dba
 */
class DatabaseAccessPDOModel extends BaseModel
{
    protected $dba;

	protected $db;
	protected $db_imm;
	protected $db_rfs;

    const DB_IMM = 'imm';
    const DB_RFS = 'rfs';

    function __construct()
    {
		parent::__construct();
        $this->setDatabases();

		$this->dba = new PDO(sprintf(DSN, $this->db->hostname, $this->db->database), $this->db->username, $this->db->password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		]);
	}

	public function loadDatabase($db_name = self::DB_RFS)
	{
		$this->db = $this->load->database($db_name, true);
		$this->dba = new PDO(sprintf(DSN, $this->db->hostname, $this->db->database), $this->db->username, $this->db->password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

		if ($this->db->conn_id === false) {
			log_message('DEBUG', 'データベースに接続されていません');
			return;
		}
	}

	public function setDatabases()
	{
		// 施設管理 DB
		$this->loadDatabase(self::DB_RFS);
		$this->db_rfs = $this->db;
		// 維持管理 DB
		$this->loadDatabase(self::DB_IMM);
		$this->db_imm = $this->db;

		// 初期接続先は施設管理系とする
		$this->db = $this->db_rfs;
	}

	/**
	 * 汎用 SELECT 関数
	 *
	 * @access public
	 *
	 * @param string $db		db_name
	 * @param string $table		table_name
	 * @param array $where		['column_name' => param]
	 * @param array $column	    ['column_name1', 'column_name2', ..]
	 * @param array $order		['column_name' => 'ASC|DESC']
	 * @param int|NULL $limit
	 * @param int|NULL $offset
	 *
	 * @return array
	 */
	public function select(
        $db,
		$table,
		$where = [],
		$column = [],
		$order = [],
		$limit = null,
		$offset = null
	) {
        // db load
        $this->loadDatabase($db);

		if ($this->db->conn_id === false) {
			log_message('DEBUG', 'データベースに接続されていません');
			return [];
        }

		$column_str = (count($column) > 0) ? implode(', ', $column) : '*';
		$query = sprintf('SELECT %s FROM %s ', $column_str, $table);

        // bind params
		$params = [];

		if (count($where) > 0) {
			$query .= 'WHERE ';

			end($where);
			$last_key_where = key($where);

			foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $query .= sprintf('%s IN (%s) ', $key, implode(',', array_fill(0, count($value), '?')));
                    foreach ($value as $v) {
                        $params[] = $v;
                    }
                } else {
                    $query .= sprintf('%s = ? ', $key);
                    $params[] = $value;
                }

				if ($last_key_where !== $key) {
					$query .= 'AND ';
				}
			}
        }

		if (count($order) > 0) {
			$order_str = 'ORDER BY ';

			end($order);
			$last_key_order = key($order);

			foreach ($order as $column_name => $order_condition) {
				$order_str .= sprintf('%s %s ', $column_name, $order_condition);

				if ($last_key_order !== $column_name) {
					$order_str .= ', ';
				}
			}

			$query .= $order_str;
		}

		if (!is_null($limit)) {
            $query .= 'LIMIT ? ';
            $params[] = $limit;

			if (!is_null($offset)) {
				$query .= 'OFFSET ? ';
                $params[] = $offset;
			}
		}

		// prepared statement ready.
		$stmt = $this->dba->prepare($query);

		if (count($params) > 0) {
			$index = 1;

			foreach ($params as $param) {
				$stmt->bindValue($index, $param);
				$index++;
			}
        }

        log_message('INFO', sprintf('%s::%s (query: %s, params: %s)', __CLASS__, __FUNCTION__, $query, implode(',', $params)));

		// query exec
		$stmt->execute();

		$rows = [];
		while ($row = $stmt->fetch()) {
			$rows[] = $row;
        }

		// transaction start
		//$this->dba->beginTransaction();

		// commit
		//$this->dba->commit();

		// rollback
		//$this->dba->rollBack();

		if (!$rows) {
            $message = sprintf('%s::%s query failed. (query: %s, params: %s)', __CLASS__, __FUNCTION__, $query, implode(',', $params));
			throw new Exception($message);
		}

		return $rows;
	}
}
