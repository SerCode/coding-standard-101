<?php

namespace SerCode\CodingStandard101\Composer;


final class ScriptHandler
{

	const RULESET = 'ruleset.xml';

	const INI_NAME = '.csStandard';
	
	const BASH_START = '#!/bin/sh';
	const PREHOOK_START = '### coding-standard-101 -start';
	const PREHOOK_END = '### coding-standard-101 -end';
	
	/**
	 * inserts prehook
	 * @return type
	 */
	public static function addPhpCsToPreCommitHook()
	{
		$originFile = getcwd() . '/.git/hooks/pre-commit';

		if(!file_exists(dirname($originFile))){
			echo 'Creating non-existent folders ...' .PHP_EOL;
			mkdir(dirname($originFile), 0777, TRUE);
		}

		$ini = self::setupIni();

		if (!isset($ini['ruleset']) || count($ini['ruleset'])==0){
			echo 'ERROR: Missing path to ' . self::RULESET . PHP_EOL;
			return -1;
		}

		$templateContent = file_get_contents(__DIR__ . '/templates/git/hooks/pre-commit-phpcs');
		$templateContent = sprintf($templateContent, $ini['ruleset'][0]);
		
		if (file_exists($originFile)) {
			$originContent = file_get_contents($originFile);
			if (strpos($originContent, self::PREHOOK_START) === FALSE) {
				echo 'Inserting pre-comit hook ...' . PHP_EOL;
				$newContent = $originContent . PHP_EOL . PHP_EOL . self::BASH_START . PHP_EOL . PHP_EOL . $templateContent;
				file_put_contents($originFile, $newContent);
			} else {
				echo 'Prehook already exist.' . PHP_EOL;
				self::updateContent($originFile, $templateContent);
			}
		} else {
			echo 'Creating file and inserting pre-comit hook ...' . PHP_EOL;
			file_put_contents($originFile,self::BASH_START . PHP_EOL . PHP_EOL .$templateContent);
		}
		chmod($originFile, 0755);
	}

	/**
	 * sets install path to codesniffer setup for using in ruleset
	 * @param type $event
	 * @return boolean
	 */
	public static function installCodeSniffStandards($event) {

		$workDir = getcwd();

		$ini = self::setupIni();

		$standardsPaths = [];

		if (isset($ini['packages']) && count($ini['packages'])>0) {
			$packages = $ini['packages'];

			foreach($packages as $package){
				$pathDir = $workDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . strtolower(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $package));
				if (is_dir($pathDir)){
					$pathRuleset = self::justFindFile($pathDir);
					if ($pathRuleset) {
						$standardsPaths[$package] = $pathRuleset;
					} else {
						echo 'WARNING: Non-existent "'.self::RULESET.'" in package "'.$package.'" => skipping => add file' . PHP_EOL ;
					}
				} else {
					echo 'WARNING: Non-existent folder with package "'.$package.'" => skipping => wrong package name?' . PHP_EOL ;
				}
			}

			if (!empty($standardsPaths)){
				$newSourceString = join(',', $standardsPaths);
			} else {
				echo PHP_EOL.'WARNING: None package is valid' .PHP_EOL;
				return FALSE;
			}

			echo PHP_EOL.'Setting up installed standards for Code Sniffer to ' . $newSourceString .PHP_EOL;
			echo shell_exec('phpcs --config-set installed_paths '. $newSourceString);
		} else {
			echo PHP_EOL.'None packages for register.' .PHP_EOL;
		}

	}

	/**
	 * findes ruleset.xml in every package
	 * @param type $searchDir
	 * @return boolean
	 */
	public static function justFindFile($searchDir){
		$it = new \RecursiveDirectoryIterator($searchDir, \RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new \RecursiveIteratorIterator($it) as $file) {
			$pathParts = explode(DIRECTORY_SEPARATOR, $file);
			$fileName = array_pop($pathParts);
			if ( $fileName === self::RULESET) {
				return dirname(dirname($file));
			}
		}
		return FALSE;
	}

	/**
	 * get ini and merge with default
	 * @return type
	 */
	public static function setupIni(){
		$workDir = getcwd();

		$defaultIniPath = __DIR__ . '/../' . self::INI_NAME;
		$overrideIniPath = $workDir . DIRECTORY_SEPARATOR . self::INI_NAME;

		$defaultIni = parse_ini_file($defaultIniPath, TRUE);

		if (file_exists($overrideIniPath)){
			$ownIni = parse_ini_file($overrideIniPath, TRUE);
		}

		if (isset($ownIni)){
			$ini = array_merge_recursive($ownIni, $defaultIni);
			$ini['packages'] = array_unique($ini['packages']);
		} else {
			$ini = $defaultIni;
		}
		
		return $ini;
	}
	
	/**
	 * finde and replace cs101 part in prehook file
	 * @param type $originFile
	 * @param type $templateContent
	 */
	public static function updateContent($originFile, $templateContent){
		$file = file($originFile);

		// need improvement
		$out = [];
		$include = true;
		$restartInclude = false;
		$hasStart = false;
		$hasEnd = false;
		foreach($file as $index => $line){
			if ($index == 0 && !(trim($line)== self::BASH_START)){
				$out[] = self::BASH_START .PHP_EOL .PHP_EOL;
			}

			if (trim($line)== self::PREHOOK_START){
				$include = false;
				$hasStart = true;
			} elseif (trim($line)== self::PREHOOK_END){
				$restartInclude = true;
				$hasEnd = true;
			}
			if ($include === true){
				$out[] = $line;
			}

			if ($restartInclude === true){
				$include = true;
				$out[] = $templateContent;
			}
		}

		if (!$hasEnd || !$hasStart){
			echo 'ERROR: Precommit hook file missing start or end part line: ' . self::PREHOOK_END . ' => file left untouched.' . PHP_EOL;
			exit;
		}

		echo 'Renewing cs101 prehook part...' . PHP_EOL;
		$fp = fopen($originFile, "w+");
		flock($fp, LOCK_EX);
		foreach($out as $line) {
				fwrite($fp, $line);
		}
		flock($fp, LOCK_UN);
		fclose($fp); 
		
	}
}
