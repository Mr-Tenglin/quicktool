<?php
namespace tenglin\quicktool\application;

class SQLite3DB extends \SQLite3 {
	private $prefix = "ejcms_";
	private $field = "*";
	private $tableName = "";
	private $join = [];
	private $where = [];
	private $orderby = [];
	private $readFind = false;
	private $sql = "";
	private $sqlAll = [];

	public function __construct($args) {
		if (!empty($args[0])) {
			$this->open($args[0]);
		}
		if (!empty($args[1])) {
			$this->prefix = $args[1];
		}
	}

	public function dbfile($dbfile) {
		$this->open($dbfile);
		return $this;
	}

	public function prefix($prefix) {
		$this->prefix = $prefix;
		return $this;
	}

	public function table($tableName) {
		$this->tableName = $tableName;
		return $this;
	}

	public function name($tableName) {
		$this->tableName = $this->prefix . $tableName;
		return $this;
	}

	public function field($field = "*") {
		$this->field = $field;
		return $this;
	}

	public function join($table, $condition, $type = "INNER") {
		list($conMain, $conSub) = explode("=", $condition);
		$this->join[] = strtoupper($type) . " JOIN " . $this->prefix . $table . " ON " . $this->prefix . trim($conMain) . " = " . $this->prefix . trim($conSub);
		return $this;
	}

	public function leftJoin($table, $condition) {
		$this->join($table, $condition, "LEFT");
		return $this;
	}

	public function rightJoin($table, $condition) {
		$this->join($table, $condition, "RIGHT");
		return $this;
	}

	public function fullJoin($table, $condition) {
		$this->join($table, $condition, "FULL");
		return $this;
	}

	public function where($prop, $operator = "=", $value = "", $cond = "AND") {
		if (is_array($prop)) {
			if (count($prop) == count($prop, 1)) {
				foreach ($prop as $k => $v) {
					$this->where[] = [$cond, $k, $operator, $v];
				}
			} else {
				foreach ($prop as $v) {
					$this->where[] = [empty($v[3]) ? "AND" : $v[3], $v[0], $v[1], $v[2]];
				}
			}
		} else {
			if (empty($value)) {
				$this->where[] = [$cond, $prop, "=", $operator];
			} else {
				$this->where[] = [$cond, $prop, $operator, $value];
			}
		}
		return $this;
	}

	public function whereOr($prop, $operator = "=", $value = "") {
		$this->where($prop, $operator, $value, "OR");
		return $this;
	}

	public function whereLike($prop, $value) {
		$this->where($prop, "LIKE", $value);
		return $this;
	}

	public function whereNotLike($prop, $value) {
		$this->where($prop, "NOT LIKE", $value);
		return $this;
	}

	public function whereIn($prop, $value) {
		$this->where($prop, "IN", $value);
		return $this;
	}

	public function whereNotIn($prop, $value) {
		$this->where($prop, "NOT IN", $value);
		return $this;
	}

	public function whereBetween($prop, $value) {
		$this->where($prop, "BETWEEN", $value);
		return $this;
	}

	public function whereNotBetween($prop, $value) {
		$this->where($prop, "NOT BETWEEN", $value);
		return $this;
	}

	public function whereTime($prop, $operator, $value) {
		$this->where($prop, $operator, $value);
		return $this;
	}

	public function order($field, $direction = "DESC") {
		if (is_array($field)) {
			foreach ($field as $k => $v) {
				$this->orderby[$k] = $v;
			}
		} else {
			$this->orderby[$field] = $direction;
		}
		return $this;
	}

	public function readfind() {
		$this->readFind = true;
		return $this;
	}

	public function find() {
		$sql = $this->_sql("SELECT " . $this->field . " FROM [" . $this->tableName . "]" . $this->_join() . $this->_where() . $this->_orderby() . ";");
		$result = $this->querySingle($sql, true);
		return $result;
	}

	public function select($limit = "") {
		$data = [];
		$sql = $this->_sql("SELECT " . $this->field . " FROM [" . $this->tableName . "]" . $this->_join() . $this->_where() . $this->_orderby() . $this->_limit($limit) . ";");
		$result = $this->query($sql);
		while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
			$data[] = $res;
		}
		return $data;
	}

	public function paginate($page, $rows = "", &$callback = []) {
		$data = [];
		if (is_array($page)) {
			$limit = [$page["page"], $page["limit"]];
		} else {
			$limit = [$page, $rows];
		}
		$sql = $this->_sql("SELECT " . $this->field . " FROM [" . $this->tableName . "]" . $this->_join() . $this->_where() . $this->_orderby() . $this->_limit($limit) . ";");
		$result = $this->query($sql);
		$callback = $this->_pageinfo($this->count(), $limit);
		while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
			$data[] = $res;
		}
		return $data;
	}

	public function count($field = "*") {
		if (empty($this->sql)) {
			return $this->querySingle("SELECT count(" . $field . ") FROM [" . $this->tableName . "]" . $this->_join() . $this->_where() . ";");
		} else {
			return $this->querySingle(str_replace("SELECT " . $field, "SELECT count(" . $field . ")", $this->sql));
		}
	}

	public function insert($data, &$error = "") {
		$sql = "INSERT INTO [" . $this->tableName . "] ";
		$sql .= "([" . implode("], [", array_keys($data)) . "])";
		$sql .= " VALUES ";
		$sql .= "('" . implode("', '", array_values($data)) . "');";
		$sql = $this->_sql($sql);
		if ($this->exec($sql)) {
			return $this->lastInsertRowID();
		}
		$error = $this->lastErrorMsg();
		return $sql;
	}

	public function insertAll($dataMulti) {
		$ids = $query = [];
		$this->exec("BEGIN;");
		foreach ($dataMulti as $data) {
			$sql = "INSERT INTO [" . $this->tableName . "] ";
			$sql .= "([" . implode("], [", array_keys($data)) . "])";
			$sql .= " VALUES ";
			$sql .= "('" . implode("', '", array_values($data)) . "');";
			$query[] = $sql;
			if ($this->exec($sql)) {
				$ids[] = $this->lastInsertRowID();
			}
		}
		$this->_sql(implode("\n", $query));
		$this->exec("COMMIT;");
		return $ids;
	}

	public function update($data) {
		$finds = [];
		$sql = "UPDATE [" . $this->tableName . "] SET ";
		foreach ($data as $k => $v) {
			$finds[] = "[" . $k . "] = '" . $v . "'";
		}
		$sql .= implode(", ", $finds) . $this->_where() . ";";
		$sql = $this->_sql($sql);
		$result = $this->exec($sql);
		return $result;
	}

	public function delete() {
		if ($this->readFind) {
			$tableName = $this->tableName;
			$_where = $this->_where();
			$sql = $this->_sql("SELECT " . $this->field . " FROM [" . $tableName . "]" . $this->_join() . $_where . $this->_orderby() . ";");
			$del = $this->_sql("DELETE FROM [" . $tableName . "]" . $_where . ";");
			$result = $this->querySingle($sql, true);
			$this->exec($del);
		} else {
			$sql = $this->_sql("DELETE FROM [" . $this->tableName . "]" . $this->_where() . ";");
			$result = $this->exec($sql);
		}
		return $result;
	}

	public function getSql($getAll = false) {
		if ($getAll) {
			return $this->sqlAll;
		} else {
			return $this->sql;
		}
	}

	private function _sql($sql) {
		$this->sql = $sql;
		$this->sqlAll[] = $sql;
		$this->_reset();
		return $sql;
	}

	private function _reset() {
		$this->field = "*";
		$this->tableName = "";
		$this->join = [];
		$this->where = [];
		$this->orderby = [];
		$this->readFind = false;
	}

	private function _join() {
		if (empty($this->join)) {
			return "";
		}
		return " " . implode(" ", $this->join);
	}

	private function _where() {
		if (empty($this->where)) {
			return "";
		}
		$build = "";
		foreach ($this->where as $cond) {
			list($concat, $varName, $operator, $val) = $cond;
			$concat = strtoupper($concat);
			$operator = strtoupper($operator);
			switch ($operator) {
			case "NOT LIKE":
			case "LIKE":
				if (is_array($val)) {
					$like = [];
					foreach ($val as $v) {
						$like[] = "[" . $varName . "] " . $operator . " '" . $v . "'";
					}
					$build .= " " . $concat . " (" . implode(" OR ", $like) . ")";
				} else {
					$build .= " " . $concat . " [" . $varName . "] " . $operator . " '" . $val . "'";
				}
				break;
			case "NOT IN":
			case "IN":
				if (!is_array($val)) {
					$value = [];
					$val = explode(",", $val);
					foreach ($val as $v) {
						$value[] = trim($v);
					}
					$val = $value;
				}
				$build .= " " . $concat . " [" . $varName . "] " . $operator . "('" . implode("', '", $val) . "')";
				break;
			case "NOT BETWEEN":
			case "BETWEEN":
				$build .= " " . $concat . " [" . $varName . "] " . $operator . " " . sprintf("%u AND %u", $val[0], $val[1]);
				break;
			case "NOT EXISTS":
			case "EXISTS":
				$build .= " " . $concat . " [" . $varName . "] " . $operator . " (" . $val . ")";
				break;
			default:
				$build .= " " . $concat . " [" . $varName . "] " . $operator . " '" . $val . "'";
			}
		}
		return " WHERE " . trim(substr($build, 4));
	}

	private function _orderby() {
		if (empty($this->orderby)) {
			return "";
		}
		$build = " ORDER BY ";
		foreach ($this->orderby as $prop => $value) {
			if (strtoupper(str_replace(" ", "", $prop)) == "RAND()") {
				$build .= "RAND(), ";
			} else {
				$build .= $prop . " " . strtoupper($value) . ", ";
			}
		}
		return rtrim($build, ", ");
	}

	private function _limit($numRows) {
		if (empty($numRows)) {
			return "";
		}
		if (is_array($numRows)) {
			if ($numRows[0] < 1) {
				$numRows[0] = 1;
			}
			return " LIMIT " . (int) $numRows[0] . ", " . (int) $numRows[1];
		} else {
			return " LIMIT " . (int) $numRows;
		}
	}

	private function _pageinfo($totalCount, $numRows) {
		$pageinfo = [];
		if (empty($numRows)) {
			$pageinfo["page"] = 1;
		} else {
			if (is_array($limit)) {
				$pageinfo["page"] = $numRows[0];
				$pageinfo["pageNumber"] = $numRows[1];
				$pageinfo["pageTotal"] = ceil($totalCount / $numRows[1]);
			} else {
				$pageinfo["page"] = 1;
				$pageinfo["pageNumber"] = $numRows;
				$pageinfo["pageTotal"] = ceil($totalCount / $numRows);
			}
		}
		$pageinfo["count"] = $totalCount;
		return $pageinfo;
	}
}
