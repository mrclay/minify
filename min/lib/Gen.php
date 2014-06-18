<?php

include_once 'JSMin.php';
include_once 'CSSmin.php';

//include_once 'Minify.php';

/**
* Will ustilzie PHP to open and dynmically generate a JS or CSS File
*/
if (isset($_GET['E_ALL']))
{
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

class Gen
{
	protected $allowed_file_types, $files, $root, $content, $extCheck, $output, $minContent;
	
	protected $mime = array('JS' => 'application/x-javascript', 'CSS' => 'text/css');
	
	function __construct ($args)
	{
		$this->allowed_file_types = array('CSS', 'JS');
		
		if (!isset($args['root'])) $this->root = $_SERVER['DOCUMENT_ROOT'];
		
		$this->root = rtrim($this->root, '/') . DIRECTORY_SEPARATOR; // ensures root ends with directory seperator

		if (!isset($args['output'])) $this->output = true;
		
		if (!isset($args['minContent'])) $this->minContent = true;
	}
	
	public function build ($files)
	{
		$cssMin = new CSSmin();
		
		$output = '';
		
		try
		{

			$this->files = $files;

			$this->files = explode(',', urldecode($this->files));
			
			if (empty($this->files)) throw new Exception("No JS or CSS Files to Generate!");


			$this->extCheck = self::_checkAllExt($this->files);
			
			foreach ($this->files as $k => $file)
			{
				$ext = self::_getFileExt($file);

				if (!in_array($ext, $this->allowed_file_types)) throw new Exception("{$ext} is not an allowed");
				

				
				// checks if file exists - if it does not, continues
				if (!file_exists($this->root . $file)) continue;
				
				$content = self::_getFileContent($this->root . $file);
				
				if (!$this->extCheck)
				{
					$content = self::_wrapContent($content, $ext);	
				}
				
				if ($this->minContent)
				{
					if ($ext == 'JS')
					{
						$content = JSMin::minify($content);
					}
					else
					{
						$content = $cssMin->run($content);	
					}
				} 
				
				$output .= $content;
			}
			
			if ($this->output)
			{
				if (!$this->extCheck) self::_output($output);
				else self::_output($output, true, $this->mime[$this->extCheck]);
			}
			

		}
		catch (Exception $e)
		{
			error_log($e);
		}
		
		
		return $output;
	}
	
	private static function _wrapContent ($content, $ext)
	{

		if ($ext == 'JS')
		{
			return PHP_EOL . "<script type='text/javascript'>" . PHP_EOL . $content . PHP_EOL . "</script>" . PHP_EOL;
		}
		elseif ($ext == 'CSS')
		{
			return PHP_EOL . "<style type='text/css'>" . PHP_EOL . $content . PHP_EOL . "</style>" . PHP_EOL;
		}
		
		return false;
	}
	
	private static function _output ($content, $withHeader = true, $type = '')
	{
		if ($withHeader && empty($type)) throw new Exception("Please specify a header content type! (text/javascript text/css)");
		
		header('Content-Type: ' . $type);
		
		exit($content);
	}
	
	/*
	* goes through each file and ensures they are all of the same type
	* Unable to output js + css together...I mean we could but..yeah
	*/
	private static function _checkAllExt ($files)
	{
		$js = $css = false;
		
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				$ext = self::_getFileExt($file);
				
				if ($ext == 'JS') $js = true;
				if ($ext == 'CSS') $css = true;
				
			}
		}

		if ($js === true && $css === true) return false;
		
		
		if ($js) return 'JS';
		if ($css) return 'CSS';
		
		return false;
	}
	
	
	// gets the content of the JS/CSS File
	private static function _getFileContent ($file)
	{
		if (empty($file)) throw new Exception("File name is empty!");
		
		$data = file_get_contents($file);
		
		if ($data === false) throw new Exception("Unable to get {$file} content");
		
		return PHP_EOL . $data . PHP_EOL;
		
	}
	

    private static function _getFileExt ($file)
    {
    	if (empty($file)) throw new Exception('File name is empty!');
     	
        $ld = strrpos($file, '.');

        // gets file extension
        $ext = strtoupper(substr($file, $ld + 1, (strlen($file) - $ld)));

		return $ext;
	}

}