<?php
//
//  Vojtech Meluzin
//  xmeluz04
//
	include_once "XML2DDL.php";
	include_once "DDL2XML.php";
	include_once "arguments.php";

	$DEBUG = false;

	//mb_internal_encoding('UTF-8');
    mb_internal_encoding('UTF-8');

	$STDERR = fopen('php://stderr', 'w+');
	$inFile;
	$outFile = null;
	$xml;
	$isRelation = false;
	$isA = false;
	$isB = false;
	$etc = null;

	// Zpracovani parametru a nastaveni globalnich promennych
	chenckArguments($argv, $argc);

    if ($DEBUG) {
        echo "\n Arguments: \n";
        echo var_dump($isA). var_dump($isB). var_dump($etc);
    }

    // Vyparsovani dat z xml
    $parser = new XML2DDL($xml, $etc, !$isA, !$isB);
    $parser->setSTDERR($STDERR)
            ->parse();

    if ($DEBUG) {
        echo "\nXML2DDL - END \n";
    }

	// Relace
	if ($isRelation) {
        $parser->findRelations(); // nalezeni relaci

        $output = new DDL2XML($parser->getResult());
        $output->makeRelations();

        if ($outFile != null) {
            if ((file_put_contents($outFile, $output->getOutPut(), LOCK_EX)) === FALSE) {
                fwrite($STDERR, "Nepodarilo ze zapsat do vystupniho souboru.\n");
                fclose($STDERR);
                exit(3);
            }
        }
        else {
            echo $output->getOutPut();
        }
	}
	// DDL
	else {
        $output = new DDL2XML($parser->getResult());
        $output->make();

        if ($outFile != null) {
            if ((file_put_contents($outFile, $output->getOutPut(), LOCK_EX | FILE_APPEND)) === FALSE) {
                fwrite($STDERR, "Nepodarilo ze zapsat do vystupniho souboru.\n");
                fclose($STDERR);
                exit(3);
            }
        }

        else {
            echo $output->getOutPut();
        }
	}
?>