# Coding Standard 101

### Easy way to implement coding standards to your project
## About
Easy
## Requirements
PHP 5.6 or higher.

## Instalation
Easiest and best option via composer.
Just add to composer.json
```
"require-dev": {
	"sercode/coding-standard-101": "~0.1"
},
```
or from command line
```
$ composer require sercode/coding-standard-101 --dev
```

## Basic usage

Run with Code sniffer for check code:

```sh
vendor/bin/phpcs src --standard=vendor/sercode/coding-standard-101/src/ruleset.xml -p
```

Run this for fix all errors in project:
````sh
vendor/bin/phpcbf src --standard=vendor/sercode/coding-standard-101/src/ruleset.xml -p
````

That's all for really base usage! This check all files in your project against default `ruleset.xml`

## How to be safe

### Checking only files in commit

In case you don't want to use Php_CodeSniffer manually for every change in the code you make, you can add pre-commit hook via `composer.json`.
**Every time you try to commit, Php_CodeSniffer will run on changed `.php` files for actual commit only.**

##### 1) Manual usage


```json
"scripts": {
    "cs-install-prehook": [
		"SerCode\\CodingStandard101\\Composer\\ScriptHandler::addPhpCsToPreCommitHook"
	]
}		
```

After you run **composer update** or **composer install** just run:
 
```
composer cs-install-prehook
```
**For proper usage all features zou have to run this script**


*If you don't change path to `ruleset.xml` (see bellow), you don't need to run this script again*

##### 2) if you are lazy or just be sure
Just add some code:

```json
"scripts": {
	"post-install-cmd": [
		"@post-update-cmd"
	],
	"post-update-cmd": [
		"@cs-install-prehook"
	],  
	"cs-install-prehook": [
		"SerCode\\CodingStandard101\\Composer\\ScriptHandler::addPhpCsToPreCommitHook"
	]
}
```

This secure install git commit prehook every time when you run  **composer update** or **composer install**.

*First case is starts after you run composer install, second when composer update and install prehook with path to `ruleset.xml`. For more information see below.*


**If you want commit without running codesniffer, just add `--no-verify` to commit command**:
```
git commit -m "TEST" --no-verify
```
### Fixing only files in commit
To `composer.json` put:

```json
"scripts": {
  "fix-cs-commit": [
    "SerCode\\CodingStandard101\\Composer\\ScriptHandler::fixCsCommit"
  ]
}
```
and run
 ```
 composer fix-cs-commit
 ```

***
## Advanced usage

***
Inspired by https://github.com/DeprecatedPackages/CodingStandard

