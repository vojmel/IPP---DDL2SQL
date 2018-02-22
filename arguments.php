<?php
//
//  Pomocne funkce pro kontrolu a zpracovani argumentu
//
//  Vojtech Meluzin
//  xmeluz04
//


//
//	Vytisknuti help
//
function helpPrint() {
    echo "--help                 - Napoveda
--input=filename.ext   - Vstupni xml soubor
--output=filename.ext  - Vystupni xml soubor
--header='text'        - Hlavicka vlozena na zacetek vystupniho souboru
--etc=num              - Maximalni počet sloupcu vzniklych ze stejnojmennych podelementu
-a                     - Nebudou se generovat sloupce z atributu
-b                     - Vice stejnych alelementu se bude brat jako jeden element (Nelze kombinovat s '--etc')
-g                     - Vystupem je XML s relacema
";
};


//
//	Zkontroluje spravnost zadanych argumentu
//  Nastavuje globalni promenne: $inFile $outFile $xml $isRelation$isA $isB a $etc
//
function chenckArguments($argv, $argc) {

    global $STDERR;
    global $inFile;
    global $outFile;
    global $isRelation;
    global $xml;
    global $isA;
    global $isB;
    global $etc;

    global $DEBUG;

    $massage = "Spatne argumenty pouzite '--help' pro napovedu.\n";

    $shortopts = "abg"; //vypis moznych kratkych argumentov
    $longopts  = array( //vypis moznych dlhych argumentov
        "help",
        "input:",
        "output:",
        "header:",
        "etc:",
        "",
    );
    $options = getopt($shortopts, $longopts);


    if ($DEBUG) {
        echo "------------------------------------------------";
        var_dump($options);
        var_dump($argv);
        var_dump($argc);
        echo "------------------------------------------------";
    }

    // Nejaky spatny argument
    if ((count($options)+1) != count($argv)) {
        fwrite($STDERR, $massage.($DEBUG? "1":""));
        fclose($STDERR);
        exit(1);
    }


    // Napoveda
    if (isset($options["help"])) {
        if ($argc == 2) {
            helpPrint();
            fclose($STDERR);
            exit(0);
        }
        fwrite($STDERR, $massage.($DEBUG? "2":""));
        fclose($STDERR);
        exit(1);
    }

    // Za 'abg' nejsou zadne hodnoty
    if(isset($options["a"])) {
        if (!(in_array("-a", $argv))) {
            fwrite($STDERR, $massage.($DEBUG? "3":""));
            fclose($STDERR);
            exit(1);
        }
        $isA = true;
    }
    if(isset($options["b"])) {
        if (!(in_array("-b", $argv))) {
            fwrite($STDERR, $massage.($DEBUG? "4":""));
            fclose($STDERR);
            exit(1);
        }
        $isB = true;
    }
    if(isset($options["g"])) {
        if (!(in_array("-g", $argv))) {
            fwrite($STDERR, $massage.($DEBUG? "5":""));
            fclose($STDERR);
            exit(1);
        }
        $isRelation = true;
    }

    // etc
    if (isset($options["etc"])) {
        $etc = $options["etc"];

        if ( ! is_numeric($etc)) {// je to cislo
            fwrite($STDERR, $massage.($DEBUG? "6":""));
            fclose($STDERR);
            exit(1);
        }
        if($etc < 0) { // je > 0
            fwrite($STDERR, $massage.($DEBUG? "7":""));
            fclose($STDERR);
            exit(1);
        }
        if (isset($options["b"])) {// neni zadan argument b
            fwrite($STDERR, "Spatne argumenty '-b' nelze zaroven s '--etc' pouzite '--help' pro napovedu.\n");
            fclose($STDERR);
            exit(1);
        }
    }


    // input
    if(isset($options["input"])) {
        if ($DEBUG) echo "--- INPUT";
        if( ! file_exists($options["input"])) {
            fwrite($STDERR, "Vstupni XML soubor neexistuje.\n");
            fclose($STDERR);
            exit(2);
        }
        if( ! is_readable($options["input"])) {
            fwrite($STDERR, "Chyba pri pokusu o otevreni vstupniho souboru.\n");
            fclose($STDERR);
            exit(3);
        }
        if(($inFile = file_get_contents($options["input"])) === FALSE) {
            fwrite($STDERR, "Chyba pri pokusu o otevreni vstupniho souboru.\n");
            fclose($STDERR);
            exit(3);
        }
    }
    else {	// neni zadan input
        if (($inFile = file_get_contents('php://stdin')) === FALSE) {
            fwrite($STDERR, "Chyba pri nacitani vstupu.\n");
            fclose($STDERR);
            exit(3);
        }
    }
    // pokus o prevod inputu
    $xml = simplexml_load_string($inFile);
    if($xml === false) {
        if(isset($options["output"])) {
            file_put_contents($options["output"], "");
            fwrite($STDERR, "Spatny vstupni XML soubor.\n");
            fclose($STDERR);
            exit(4);
        }
        else { // Predpoklada se spravny vstupni xml soubor
            fwrite($STDERR, "Spatny vstupni XML soubor.\n");
            fclose($STDERR);
            exit(4);
        }
    }


    // output
    if(isset($options["output"])) {
        $outFile = $options["output"];
        if ((file_put_contents($outFile, "")) === FALSE) {
            fwrite($STDERR, "Nepodarilo ze zapsat do vystupniho souboru.\n");
            fclose($STDERR);
            exit(3);
        }

        // zapsani hlavicky hlavicky
        if (isset($options["header"])) {
            if ( ! $isRelation) { // pokud se nezapisuji relace
                if ((file_put_contents($outFile, "--" . $options["header"] . "\n\n")) === FALSE) {
                    fwrite($STDERR, "Nepodarilo ze zapsat do vystupniho souboru.\n");
                    fclose($STDERR);
                    exit(3);
                }
            }
        }
    }
    else {
        // vypsani hlavicky hlavicky
        if (isset($options["header"])) {
            if ( ! $isRelation) { // pokud se nezapisuji relace
                echo "--" . $options["header"] . "\n\n";
            }
        }
    }
}