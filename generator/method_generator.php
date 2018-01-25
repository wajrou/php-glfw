<?php
/**
 * This file contains all classes and methods needed
 * to generate the PHP extensions methods including their 
 * argument info and function entry.
 */

/**
 * Base argument
 */
abstract class Argument 
{
	public $name;
	public $charid;
	public $passedByRef = false;
	public $realType = 'void';
	
	public function __construct(string $name) 
	{ 
		if ($name[0] === '&') {
			$this->passedByRef = true;
			$name = substr($name, 1);
		}

		if (strpos($name, ':') !== false) {
			list($name, $this->realType) = explode(':', $name);
		}

		$this->name = $name; 
	}

	abstract public function generateVariable() : string;

	public function getVariable() {
		if ($this->passedByRef) {
			return "zval *z_{$this->name};";
		}
		return $this->generateVariable();
	}

	public function getReferences() {
		if ($this->passedByRef) {
			return '&z_' . $this->name;
		}
		return '&' . $this->name;
	}

	public function getCharId() : string {
		if ($this->passedByRef) {
			return 'z';
		}
		return $this->charid;
	}

	public function generateZValConvertion() : string
	{
		return '';
	}

	public function generateDereferencing() : string 
	{
		if (!$this->passedByRef) {
			return '';
		}

		$b = "ZVAL_DEREF(z_{$this->name});";
		$b .= $this->generateZValConvertion();

		return $b;
	}

	public function getCallName()
	{
		if ($this->passedByRef) {
			return "&z_{$this->name}->value";
		}

		return $this->name;
	}
}

/**
 * Argument types
 */
class LongArgument extends Argument {
	public $charid = 'l';
	public function generateVariable() : string {
		return "zend_long {$this->name};";
	}
	public function generateZValConvertion() : string {
		return " convert_to_long(z_{$this->name});";
	}
	public function getCallName() {
		if ($this->passedByRef) {
			return "({$this->realType} *)" . parent::getCallName();
		}
		return parent::getCallName();
	}
}
class FloatArgument extends Argument {
	public $charid = 'f';
	public function generateVariable() : string {
		return "float {$this->name};";
	}
}
class DoubleArgument extends Argument {
	public $charid = 'd';
	public function generateVariable() : string {
		return "double {$this->name};";
	}
}
class BoolArgument extends Argument {
	public $charid = 'b';
	public function generateVariable() : string {
		return "zend_bool {$this->name};";
	}
}
class StringArgument extends Argument {
	public $charid = 's';
	public function generateVariable() : string {
		return "char *{$this->name};\nsize_t {$this->name}_size;";
	}
	public function getReferences() {
		return '&' . $this->name . ', &' . $this->name . '_size';
	}
}
class WindowArgument extends Argument {
	public $charid = 'r';
	public function generateVariable() : string {
		return "zval *{$this->name}_resource;";
	}
	public function getReferences() {
		return "&{$this->name}_resource";
	}
}
class HashTableArgument extends Argument {
	public $charid = 'h';
	public function generateVariable() : string {
		return "HashTable *{$this->name}; zval *{$this->name}_data;";
	}
}

/**
 * Base method objects
 */
abstract class Method 
{
	public $name = null;
	public $returns = 'void';
	public $arguments = [];

	/**
	 * Does the method have arguments
	 */
	public function hasArguments() {
		return !empty($this->arguments);
	}

	/**
	 * Get an array of argument objects
	 */
	public function getArguments() : array
	{
		$args = [];

		foreach ($this->arguments as $name => $type) 
		{
			switch ($type) 
			{
				case 'long': $args[] = new LongArgument($name); break;
				case 'float': $args[] = new FloatArgument($name); break;
				case 'double': $args[] = new DoubleArgument($name); break;
				case 'bool': $args[] = new BoolArgument($name); break;
				case 'string': $args[] = new StringArgument($name); break;
				case 'window': $args[] = new WindowArgument($name); break;
				case 'ht': $args[] = new HashTableArgument($name); break;
			}
		}

		return $args;
	}

	/**
	 * Prefix every line of the given text
	 *
	 * @param string 		$test
	 * @param string 		$prefix
	 */
	private function tabulate(string $text, string $prefix = '    ') : string
	{
		$lines = array_filter(explode("\n", $text));
		foreach($lines as &$line) 
		{
			$line = $prefix . $line;
		}

		return implode("\n", $lines);
	}

	/**
	 * Generate the argument definition name
	 */
	protected function generateArgInfoName(string $name) : string
	{
		return "arginfo_{$name}";
	}

	/**
	 * Generate the function entry
	 */
	public function generateFE() : string
	{
		$arginfo = 'NULL';
		if ($this->hasArguments()) {
			$arginfo = $this->generateArgInfoName($this->name);
		}

		return "PHP_FE({$this->name}, {$arginfo})";
	}

	/**
	 * Generates the argument info definition
	 */
	protected function generateArgInfo() : string
	{
		$arginfo = $this->generateArgInfoName($this->name);

		$b = "ZEND_BEGIN_ARG_INFO_EX({$arginfo}, 0, 0, 1)" . PHP_EOL;
		foreach($this->getArguments() as $argument)
		{
			$b .= "    ZEND_ARG_INFO(". (int) $argument->passedByRef .", $argument->name)" . PHP_EOL;
		}

		$b .= "ZEND_END_ARG_INFO()" . PHP_EOL;
		
		return $b;
	}

	protected function generateWindowResourceFetchCode() : string
	{
		return "GLFWwindow* window = phpglfw_fetch_window(window_resource TSRMLS_CC);\n";
	}

	protected function generateArgumentParser() : string
	{
		$b = "";

		$typecharlist = '';
		$referencelist = [];

		$hasWindowArg = false;

		// generate the vars
		foreach($this->getArguments() as $argument)
		{
			$typecharlist .= $argument->getCharId();
			$referencelist[] = $argument->getReferences();

			// add the variable
			$b .= $argument->getVariable() . "\n";

			if ($argument instanceof WindowArgument) {
				$hasWindowArg = true;
			}
		}

		// create the parser method
		$b .= PHP_EOL . 'if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "'.$typecharlist.'", '.implode(', ', $referencelist).') == FAILURE) {' . PHP_EOL;
		$b .= '   return;' . PHP_EOL;
		$b .= "}\n";

		// generate dereferencing and converting
		foreach($this->getArguments() as $argument)
		{
			$b .= $argument->generateDereferencing() . "\n";
		}

		// when we have a window argument we need to fetch it
		if ($hasWindowArg) {
			$b .= $this->generateWindowResourceFetchCode();
		}

		return $b;
	}

	/**
	 * Generate the method call
	 */
	protected function generateCallReturnVoid(string $call) : string
	{
		return $call . ";\n";
	}

	/**
	 * Generate the method call (Bool)
	 */
	protected function generateCallReturnBool(string $call) : string
	{
		// Im not sure whats exactly behind the `RETURN_TRUE` and `RETURN_FALSE`
		// so this code is probably stupid and should be return $call..
		return 'if ('. $call .') { RETURN_TRUE; } RETURN_FALSE;';
	}

	/**
	 * Generate the method call (String)
	 */
	protected function generateCallReturnString(string $call) : string
	{
		return 'RETURN_STRING('. $call .');';
	}

	/**
	 * Generate the method call (Int)
	 */
	protected function generateCallReturnInt(string $call) : string
	{
		return 'RETURN_INT('. $call .');';
	}

	/**
	 * Generate the method call
	 */
	protected function generateCall() : string
	{
		$arguments = [];

		foreach($this->getArguments() as $argument) 
		{
			$arguments[] = $argument->getCallName();
		}

		$callCode = $this->name . '('. implode(', ', $arguments) .')';

		$returnCodeGenerator = 'generateCallReturn' . ucfirst($this->returns);

		return $this->{$returnCodeGenerator}($callCode);
	}

	/**
	 * Generates the method 
	 */
	protected function generateMethod() : string
	{
		$b = "PHP_FUNCTION($this->name)\n{\n";
		if ($this->hasArguments()) {
			$b .= $this->tabulate($this->generateArgumentParser());
		}

		$b .= PHP_EOL;

		$b .= $this->tabulate($this->generateCall());

		$b .= "\n}\n";

		return $b;
	}

	/**
	 * Main generator call
	 */
	public function generate()
	{
		$c = "/**\n * {$this->name}\n * --------------------------------\n */\n";

		if (!$this->hasArguments()) {
			return $c . $this->generateMethod();
		}

		return $c . $this->generateArgInfo() . PHP_EOL . $this->generateMethod();
	}
}