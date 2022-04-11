<?php
namespace tenglin\quicktool\application;

use Exception;

class Template {
	public $view_path = './';
	public $view_suffix = '.html';
	public $tpl_begin = '{';
	public $tpl_end = '}';
	private $assign = [];

	public function __construct($args) {
		if (!empty($args[0]['path'])) {
			$this->view_path = $args[0]['path'];
		}
		if (!empty($args[0]['suffix'])) {
			$this->view_suffix = $args[0]['suffix'];
		}
		if (!empty($args[1]['begin'])) {
			$this->tpl_begin = $args[1]['begin'];
		}
		if (!empty($args[1]['end'])) {
			$this->tpl_end = $args[1]['end'];
		}
	}

	public function path($path) {
		$this->view_path = $path;
		return $this;
	}

	public function suffix($suffix) {
		$this->view_suffix = $suffix;
		return $this;
	}

	public function tplBegin($begin) {
		$this->tpl_begin = $begin;
		return $this;
	}

	public function tplEnd($end) {
		$this->tpl_end = $end;
		return $this;
	}

	public function assign($key, $value) {
		$this->assign[$key] = $value;
		return $this;
	}

	public function display($template, array $assign = null) {
		if (!empty($assign)) {
			foreach ($assign as $key => $value) {
				$this->assign[$key] = $value;
			}
		}
		return $this->compile($this->view_path . $template . $this->view_suffix);
	}

	private function compile($file) {
		if (is_file($file)) {
			$string = file_get_contents($file);
			if (preg_match_all('#' . $this->tpl_begin . 'include\s+file=["|\'](.+)["|\']' . $this->tpl_end . '#U', $string, $matches)) {
				for ($i = 0; $i < count($matches[0]); $i++) {
					$string = str_replace($matches[0][$i], file_get_contents($this->view_path . $matches[1][$i] . $this->view_suffix), $string);
				}
			}
			return $this->parse($string);
		} else {
			throw new Exception('Missing template file ' . $file);
		}
	}

	private function parse($string) {
		$keys = [
			'if %%' => '<?php if (\1): ?>',
			'elseif %%' => '<?php ; elseif (\1): ?>',
			'else' => '<?php ; else: ?>',
			'/if' => '<?php endif; ?>',
			'for %%' => '<?php for (\1): ?>',
			'/for' => '<?php endfor; ?>',
			'foreach %%' => '<?php foreach (\1): ?>',
			'/foreach' => '<?php endforeach; ?>',
			'while %%' => '<?php while (\1): ?>',
			'/while' => '<?php endwhile; ?>',
			'continue' => '<?php continue; ?>',
			'break' => '<?php break; ?>',
			'$%% = %%' => '<?php $\1 = \2; ?>',
			'$%%++' => '<?php $\1++; ?>',
			'$%%--' => '<?php $\1--; ?>',
			'$%%' => '<?php echo $\1; ?>',
			'php' => '<?php /*',
			'/php' => '*/ ?>',
			'/*' => '<?php /*',
			'*/' => '*/ ?>',
			':%%' => '<?php \1; ?>',
		];

		foreach ($keys as $key => $val) {
			$patterns[] = '#' . str_replace('%%', '(.+)', preg_quote($this->tpl_begin . $key . $this->tpl_end, '#')) . '#U';
			$replace[] = $val;
		}
		$template = preg_replace($patterns, $replace, $string);
		return $this->evaluate($template, $this->assign);
	}

	private function evaluate($code, array $variables = NULL) {
		if ($variables != NULL) {
			extract($variables);
		}
		return eval('?>' . $code);
	}
}
