<?php
namespace tenglin\quicktool;

/*
== Pinyin ==
helper::Pinyin(string)[->placeholder(string $string = "_")[->allow_chars(string $allow = "_")]]->toArray();
helper::Pinyin(string)[->placeholder(string $string = "_")[->allow_chars(string $allow = "_")]]->all(string $placeholder = "");
helper::Pinyin(string)[->placeholder(string $string = "_")[->allow_chars(string $allow = "_")]]->first(string $placeholder = "");
helper::Pinyin(string)[->placeholder(string $string = "_")[->allow_chars(string $allow = "_")]]->one();

== Template ==
helper::Template(array $view[path, suffix], array $tpl[begin, end]) // = $tpl
[$tpl->path(string $path = "./")]
[$tpl->suffix(string $suffix = ".html")]
[$tpl->tplBegin(string $begin = "{")]
[$tpl->tplEnd(string $end = "}")]
$tpl->assign(string $key, string|array $value)
$tpl->display(string $template, array $assign)

== GoogleAuthenticator ==
helper::GoogleAuthenticator(int $codeLength = 6)->createSecret(int $secretLength = 16); // = $secret
helper::GoogleAuthenticator(int $codeLength = 6)->getQRCodeGoogleUrl("Blog", $secret);
helper::GoogleAuthenticator(int $codeLength = 6)->getCode($secret); // = $oneCode
helper::GoogleAuthenticator(int $codeLength = 6)->verifyCode($secret, $oneCode, 2); // = $checkResult

== SQLite3DB ==
helper::SQLite3DB([dbfile, [prefix]]) // = $db
[$db->dbfile(file)]
[$db->prefix(prefix)]
$db->table("ejcms_table") // or $db->name("table")
$db->field("*")
$db->join(string $table, string $condition, string $type = "INNER")
$db->leftJoin(string $table, string $condition)
$db->rightJoin(string $table, string $condition)
$db->fullJoin(string $table, string $condition)
$db->where(string|array $prop, string $operator = "=", string $value = "", string $cond = "AND")
$db->whereOr(string|array $prop, string $operator = "=", string $value = "")
$db->whereLike(string|array string $prop, string $value)
$db->whereNotLike(string|array $prop, string $value)
$db->whereIn(string|array $prop, string $value)
$db->whereNotIn(string|array $prop, string $value)
$db->whereBetween(string|array $prop, string $value)
$db->whereNotBetween(string|array $prop, string $value)
$db->whereTime(string|array $prop, string $operator, string $value)
$db->order(string|array $field, $direction = "DESC")
$db->find()
$db->select(string $limit = "")
$db->paginate(string $page, string $rows = "", array &$callback = [])
$db->count(string $field = "*")
$db->insert(array $data, string &$error = "")
$db->insertAll(array $dataMulti)
$db->update(array $data)
$db->delete()
$db->getSql(boolean $getAll = false)

== Sftp ==
helper::Sftp(string|array $server[, string $user, string $password, [int $port]], string $user, string $password [,int $port]) // = $sftp
$sftp->test()
$sftp->pwd()
$sftp->is_dir(string $directory)
$sftp->mkdir(string $directory, boolean $chmod = true)
$sftp->rmdir(string $remote_path)
$sftp->scandir(string $path)
$sftp->upload_dir(string $local_path, string $remote_path)
$sftp->download_dir(string $remote_dir, string $local_dir)
$sftp->is_file(string $remote_file)
$sftp->touch(string $remote_file, string $content = "")
$sftp->upload(string $local_file, string $remote_file)
$sftp->rename(string $current_filename, string $new_filename)
$sftp->delete(string $remote_file)
$sftp->download(string $remote_file, string $local_file)

== Array2Xml ==
helper::Array2Xml(array $array, string $rootElement = "", boolean $spacesKey = true, string $encoding = null, string $version = 1.0, array $attr = [], string $sta = null) // = $to
$to->toXml();

== Xml2Array ==
helper::Xml2Array(string $xml) // = $to
$to->toArray();
 */

class helper {
	public static function create_load_function($name, $arguments = []) {
		$namespace = ucfirst($name);
		$application = "\\tenglin\\quicktool\\application\\" . $namespace;
		return new $application($arguments);
	}

	public static function __callStatic($name, $arguments) {
		return self::create_load_function($name, $arguments);
	}
}
