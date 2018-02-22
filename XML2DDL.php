<?php
//
//  Trida XML2DDL
//  Prevod xml objektu na pole tabulek
//  Pokud nastane chyba vypise se na STDERR a script skonci chybou 90
//
//  Vojtech Meluzin
//  xmeluz04
//

include_once "Table.php";
include_once "Coloumn.php";

class XML2DDL
{
    private $xmlObject;     // simpleXml vstupni objekt
    private $tables;        // vsechny tabulky

    // Nastaveni
    private $ect = null;    // kolikrat se muze opakovat sloupec null - neomezene
    private $attrbutes = false;  // zpracovani attributu (-a)
    private $moreElements = false; // omezenani na vice podelementu  (-b)

    // DEBUG
    private $DEBUG = false;

    private $STDERR;
    private $xmlParsed = false;

    private $relationTables = null;


    function __construct($xmlObject, $ect = null, $zpracovavatAtributy = true, $vicePodlementu = true)
    {
        $this->xmlObject        = $xmlObject;
        $this->ect              = $ect;
        $this->attrbutes        = $zpracovavatAtributy;
        $this->moreElements     = $vicePodlementu;

        return $this;
    }


    //
    //  Ukonci script s chybovou hlaskou
    private function error($message) {
        fwrite($this->STDERR, $message);
        fclose($this->STDERR);
        exit(90);
    }


    //
    //  Vrati tabulky
    public function getResult(){
        if ( ! $this->xmlParsed)
            $this->parse();

        return $this->tables;
    }


    public function setSTDERR($STDERR)
    {
        $this->STDERR = $STDERR;
        return $this;
    }



    //
    //  Relace
    //
    //
    //  Hledani relaci mezi tabulkama
    public function findRelations()
    {

        if ( ! $this->xmlParsed)
            $this->parse();

        $this->print_d("\nRELACE - START \n");


        // Zjisteni dostupnych tabulek pro relaci
        // pro kazdy root element
        foreach ($this->xmlObject as $name => $child) {

            $this->relationTables = Array();

            $this->print_d("\n add root ".$name);

            // nalest vsechny pod elementy
            $this->goThroughAllChild($name, $child);

            if($this->DEBUG) var_dump($this->relationTables);

            // zapamatujeme si co ma nejhlavnejsi
            $this->getTable($name)->setRelationTables($this->relationTables);

            // vsem podelementum nastavit tabulky
            foreach ($this->relationTables as $tableName => $relation) {
                $this->getTable($tableName)->setRelationTables($this->relationTables);
            }
        }

        // detam nastavime to co maji root rodicove
        foreach ($this->xmlObject as $name => $child) {

            $relTables = $this->getTable($name)->getRelations();

            // vsem podelementum nastavit tabulky
            foreach ($relTables as $tableName => $relation) {
                $this->getTable($tableName)->setRelationTables($relTables);
            }
        }

        $this->print_d("\nRELACE - Podrazene \n");


        // Podrazene vazby
        foreach ($this->xmlObject as $name => $child)
            $this->findRelationsPodrazene(null, $name, $child);



        if ($this->DEBUG) $this->printTablesRelations();

        $this->print_d("\nRELACE - Nadrazene \n");


        // 1:1 vazby
        foreach ($this->tables as $table)
            $table->setRelationRovna($table->getName());


        if ($this->DEBUG) $this->printTablesRelations();

        $this->print_d("\nRELACE - N:M \n");


        // N:M vazby
        $this->findRelationsNKuM();



        if ($this->DEBUG) $this->printTablesRelations();


        $this->print_d("\nRELACE - END \n");

        return $this;
    }

    //
    //  Rekurzivni prochazeni xml objektu pro hledani vazby
    //  Podrazene a zapamatuje si i vazbu na podrazene
    private function findRelationsPodrazene($parentName, $name, $childObjects)
    {
        $name = strtolower($name);
        $parentName = strtolower($parentName);

        $this->print_d( "\n----".$name."   (".$parentName.") ".count($childObjects));
        $table = $this->getTable($name);

        if (count($childObjects) == 0)
            return;

        // Vsem podobjektum nastavim, ze aktualni je nadrazeny
        foreach ($childObjects as $childName => $child) {
            $childName = strtolower($childName);

            // Obsahuje child odkaz na aktualni?
            if ( ! $this->getTable($childName)->coloumnExistLike($name)) {
                $this->print_d( "\n\t > ".$childName);
                // child - podrizena
                $this->getTable($childName)->setRelationPodrezenaK($name);
                // actual nadrizena
                $table->setRelationNadrezenaK($childName);

                // child dedi veskere podrizene vazby actual
                // nastavime rovnou i nadrizenou relaci
                foreach ($table->getRelationsPodrazeneK() as $tablename) {
                    // Podrizena
                    $this->getTable($childName)->setRelationPodrezenaK($tablename);
                    // Nadrizena
                    $this->getTable($tablename)->setRelationNadrezenaK($childName);
                }

            // Podrazeny obsahuje odkaz na aktualni
            } else {
                $this->print_d( "\n\t < ".$childName);

                // child - uz ma na aktualni podrazenou relaci
                if (($this->getTable($childName)->getRelation($name) != null)
                    and ($table->getRelation($childName) != null)
                    and ($this->getTable($childName)->getRelation($name) == "1:N")){
                    $this->print_d( "  - UZ ma relaci => N:M");
                    // bude to relace N:M
                    // child - N:M
                    $this->getTable($childName)->setRelationNKuM($name);
                    // actual N:M
                    $table->setRelationNKuM($childName);
                }
                // jeste mezi nema nic neni
                else {
                    $this->print_d( "");
                    // child - nadrizena
                    $this->getTable($childName)->setRelationNadrezenaK($name);
                    // actual podrizena
                    $table->setRelationPodrezenaK($childName);
                }
            }
            $this->findRelationsPodrazene($name, $childName, $child);
        }

    }

    //
    //  Rekurzivni prochazeni xml objektu pro hledani vazby
    //  Podrazene a zapamatuje si i vazbu na podrazene
    private function goThroughAllChild($name, $childObjects)
    {
        $name = strtolower($name);

        $this->print_d( "\n add ".$name."  ".count($childObjects));
        $this->relationTables[$name] = null;

        if (count($childObjects) == 0)
            return;

        // Vsem podobjektum nastavim, ze aktualni je nadrazeny
        foreach ($childObjects as $childName => $child) {
            $childName = strtolower($childName);
            $this->goThroughAllChild($childName, $child);
        }

    }


    //
    //  Najde NkuM relace
    private function findRelationsNKuM()
    {
        // vyhledame takove, ktere jeste nemaji vsechny vazby
        foreach ($this->tables as $table) {
            if ( ! $table->hasAllRelations() ) {
                $this->print_d("\nT: ". $table->getName());

                foreach ($table->getNotSettedRelations() as $relaceTableName){
                    $this->print_d("\n\t R:".$relaceTableName);

                    $this->print_d("\n\t add N:M");
                    $table->setRelationNKuM($relaceTableName);
                }
            }
        }

    }


    //
    //  Parsovani xml objektu na tabulky
    //
    // Parsuje xml objekt
    public function parse()
    {
        $this->print_d("\nPARSER - START \n");

        // Zavolame rekurzivni prochazeni xml objektu
        $this->parseChild(null, $this->xmlObject);

        if (count($this->tables) < 1)
            return null;


        $this->print_d( "\n\n\nValues tables\n");

        // Value tables
        foreach ($this->tables as $table) {
            $this->print_d("\nT: " . $table->getname());

            if ($table->getColumns() != null)
                foreach ($table->getColumns() as $column) {

                    $this->print_d("\n\t" . $column->getNameForDB() . " " . $column->getDataTypeForDB());

                    // Mame pro tuto tabulku vytvorit value tabulku?
                    if ($column->isCreateValTable()) {
                        // pridani value tabulky
                        $this->print_d("\n\t\tadd val table " . $column->getNameWithNoRank() . "(" . $column->getDataType() . ")");
                        $this->addValueTable($column->getNameWithNoRank(), $column->getDataType(), $table->getname());
                        // Update coloumn datatypu
                        $column->setValueDatatype(10);
                    }
                }
        }


        $this->print_d("\nETC\n");


        // ECT
        if ($this->ect != null)
            // Nanachazi se v nejake tabulce vice sloupcu s podobnym nazvem
            foreach ($this->tables as $table) {
                $this->print_d( "\nT: ".$table->getname());
                if ($table->getColumns() != null)
                    foreach ($table->getColumns() as $column) {
                        $this->print_d( "\n\t".$column->getNameForDb()." ".$table->numOfColoumnsLike($column->getName()));

                        // jen pro neatributove
                        if ($column->isAddId()
                            and $column->isCanDelete()
                        ) {
                            $this->print_d(" -- check");
                            if ($this->ect == 0) { // vsechny podelementy jsou do samostatne
                                if ($column->isCreateValTable()) {

                                    $this->print_d(" - REMOVED");
                                    $table->removeColoumn($column->getName());

                                    $this->print_d("\n\t\tadd " . $table->getname() . "(" . Coloumn::getDataTypeName(10) . ") to " . $column->getNameWithNoRank());

                                    $this->getTable($column->getNameWithNoRank())
                                        ->addColoumn($table->getname(), 10, true)
                                        ->setCantDelete(false);
                                }
                            }

                            if (($table->numOfColoumnsLike($column->getName())) > $this->ect) {
                                $this->print_d(" - REMOVED");

                                $table->removeAllColoumnsLike($column->getName());

                                $this->print_d("\n\t\tadd " . $table->getname() . "(" . Coloumn::getDataTypeName(10) . ") to " . $column->getNameWithNoRank());

                                $this->getTable($column->getNameWithNoRank())
                                    ->addColoumn($table->getname(), 10, true)
                                    ->setCantDelete(false);
                            }
                        }
                        else{
                            $this->print_d(" -- continue");
                        }
                    }
            }

        $this->print_d( "\nPARSER - END\n");

        if ($this->DEBUG) $this->printTablesDB();

        $this->xmlParsed = true;
        return $this;
    }


    // Kontrola jestli je child xml elementu hodnota
    private function isChildValue($child) {
        if (($child != null) and (trim($child) != ""))
            return true;
        return false;
    }


    // Parsovani jedne tabulky
    private function parseTable($name, $parentName, $child) {

        $name = strtolower($name);
        $parentName = strtolower($parentName);


        $this->print_d( "\n----".$name."   (".$parentName.") ".count($child));
        $this->addTable($name);

        $addedColoums = array(); // pridane sloupce v aktualnim zpracovani tabulky
        $addedColoumsAttr = array(); // pridane sloupce v aktualnim zpracovani tabulky

        // sloupce z attributu
        if ( $this->attrbutes ) // Zpracovat attributy?
        foreach($child->attributes() as $coloumnName => $coloumnVal) {

            $coloumnName = strtolower($coloumnName);
            $pocetOpakovani = (in_array($coloumnName, $addedColoumsAttr))? array_count_values($addedColoumsAttr)[$coloumnName]: 0;
            if ($pocetOpakovani > 0) {
                $this->error("Doslo ke kolizi subelementu a atributu.\n");
            }
            else {
                // Kontrola jestli uz takovy sloupec neexistuje jako subelement
                $mayColoumn = $this->getTable($name)->findColoumnDB($coloumnName);
                if ($mayColoumn != null) {
                    if ($mayColoumn->isAddId()) {
                        $this->error("Doslo ke kolizi subelementu a atributu.\n");
                    }
                }

                $this->print_d( "\n\t\tadd attr " . $coloumnName . " (" . Coloumn::getDataTypeName($coloumnVal) . ") to " . $name);
                $this->addColoumnToTable($name, $coloumnName, $coloumnVal);
            }
            $addedColoumsAttr[] = $coloumnName;
        }

        // Pokud se sem nahodou dostal sloupec
        if (count($child) == 0) {

            // sloupecek s hodnotou
            if ($this->isChildValue($child)) {

                // Uprava datoveho typu pro value tabulky
                $dataType = Coloumn::getDataTypeName($child);
                if ($dataType == COLOUMN_DATATYPE::NVARCHAR_D)
                    $dataType = COLOUMN_DATATYPE::NTEXT_D;

                $this->print_d( "\n\t\tadd value (" . $dataType . ") to " . $name);
                $this->getTable($name)->addColoumn("value", $child)
                    ->setDataType($dataType);
            }

            return;
        }

        // sloupce z obsahu
        foreach ($child as $coloumnName => $coloumnVal){

            $coloumnName = strtolower($coloumnName);

            if (count($coloumnVal) == 0) // pouze pokud jiz prvek nema deti
            // Atributy slopce - sloupec je hned tabulkou
            if ( $this->attrbutes ) // Zpracovat attributy?
                if (count($coloumnVal->attributes()) > 0) {
                    $this->print_d("\n\t\tattribute add table ".$coloumnName." (".$name.")");

                    // zalozme pro ne tabulku
                    $this->addTable($coloumnName, $name); // automaticky je to value tabulka

                    // pridame je
                    foreach ($coloumnVal->attributes() as $attrname => $attrVal) {

                        $this->print_d("\n\t\t\tadd " . $attrname ." " . "(" . Coloumn::getDataTypeName($attrVal) . ") to " . $coloumnName);
                        $this->addColoumnToTable($coloumnName, $attrname, $attrVal)
                            ->setCreateValTable(false)
                            ->setAddId(false);
                    }
                }

            $pocetOpakovani = (in_array($coloumnName, $addedColoums)) ? array_count_values($addedColoums)[$coloumnName] : 0;

            if ($pocetOpakovani > 0) { // Dalsi sloupecek

                if (!$this->moreElements) { // maximalni pocet opakovani je jedna
                    // pripadny update datoveho typu
                    $c = $this->getTable($name)->findColoumnDB($coloumnName);
                    if ($c!=null){
                        $c->setDataTypeValue($coloumnVal);
                    }
                    continue;
                }

                // Uz existuje takovy atributovy sloupec
                $mayColoumn = $this->getTable($name)->findColoumnDB($coloumnName);
                if ($mayColoumn != null) {
                    if ( ! $mayColoumn->isAddId()) {
                        $this->error("Doslo ke kolizi subelementu a atributu.\n");
                    }
                }


                // U prvniho nastavime postfix na 1
                if ($pocetOpakovani == 1) {
                    // Existuje uz takovy s postfixem?
                        $this->print_d("\n\t\trename " . $coloumnName . " to " . $coloumnName . "1");
                        $coloumn = $this->getTable($name)
                            ->setColoumnName($coloumnName, $coloumnName . "1");

                        // Pri opakovanem projiti stejne tabulky uz neni bez posfixu
                        if ($coloumn != null) {
                            $coloumn->setCreateValTable(false)
                                ->setAddId(true);

                            if ($this->isChildValue($coloumnVal)) { // pouze pokud jiz prvek nema deti
                                // je to odkaz do value tabulky
                                $coloumn->setCreateValTable(true);

                                $this->print_d("  -- IS VAL");
                            }
                            else {
                                $this->print_d("  -- NOT VAL");
                            }
                        }
                }

                $this->print_d("\n\t\tadd " . $coloumnName . ($pocetOpakovani + 1) . "(" . Coloumn::getDataTypeName($coloumnVal) . ") to " . $name);
                $this->addColoumnToTable($name, $coloumnName. ($pocetOpakovani + 1), $coloumnVal)
                    ->setCreateValTable(false)
                    ->setAddId(true);



            } else { // Prvni sloupecek


                // Neexistuje uz nahodou takovy to attribut?
                if ($this->getTable($name)->coloumnAttrExist($coloumnName."_id") != null) {
                    $this->error("Doslo ke kolizi ciziho klice a atributu.\n");
                }

                // Existuje uz takovy s postfixem?
                if ( ! $this->getTable($name)->coloumnExistLike($coloumnName, $hledatBezAttributu = true)) {

                    // Pokud pod sebou nema hodnotu neni to value table, ale jen tabulka
                    if ( ! $this->isChildValue($coloumnVal)) {
                        $this->print_d( "\n\t\tadd table ".$coloumnName." ");
                        $this->addTable($coloumnName);
                        $this->print_d("\n\t\tadd " . $coloumnName . "(" . Coloumn::getDataTypeName($coloumnVal) . ") to " . $name. " NOT VAL");
                        $newColoumn = $this->addColoumnToTable($name, $coloumnName, $coloumnVal, true)
                            ->setCreateValTable(false)
                            ->setAddId(true);

                    } else {
                        $this->print_d("\n\t\tadd " . $coloumnName . "(" . Coloumn::getDataTypeName($coloumnVal) . ") to " . $name);
                        $newColoumn = $this->addColoumnToTable($name, $coloumnName, $coloumnVal, true)
                            ->setCreateValTable(true)
                            ->setAddId(true);
                    }

                }
                else {
                    $this->print_d("\n\t\texist ".$coloumnName." ");
                    $this->getTable($name)->setDataTypeToAllColoumnsLike($coloumnName, $coloumnVal);

                    // Pokud jeden z elementu nahodou ma hodnotu
                    if ($this->isChildValue($coloumnVal)) {
                        $this->getTable($name)->findColoumn($coloumnName,  $hledatBezAttributu = true)->setCreateValTable(true);
                    }
                }
            }

            $addedColoums[] = $coloumnName;
        }

        // tabulky
        foreach ($child as $table => $tableContent)
            if (count($tableContent) > 0)
                $this->parseTable($table, $name, $tableContent);

    }

    private function parseChild($parentName, $parent) {

        foreach ($parent as $name => $child) {

            $this->parseTable(strtolower($name), strtolower($parentName), $child);
        }
    }


    //
    // Pomocne funkce pro obsluhu tabulek a sloupecku tabulek
    //

    private function addColoumnToTable($tableName, $coloumnName, $dataType, $addID = false) {

        // prida sloupec do tabulky
        $table = $this->getTable($tableName);
        if ($table == null)
            return null;

        return $table->addColoumn( $coloumnName, $dataType, $addID);
    }

    //
    //  Prida tabulku a vrati jeji instanci
    //  Pokud jiz existuje vrati instanci existujici
    private function addTable($tableName, $parentName = null) {
        if ( ! $this->isTableExist($tableName)) {
            $this->tables[] = new Table($tableName, $parentName);
            return end($this->tables);
        }
        return $this->getTable($tableName);
    }

    //
    //  Vyhleda tabulku
    private function getTable($tableName) {
        if (count($this->tables) > 0)
            foreach ($this->tables as $table)
                if (strcmp($table->getName(),$tableName) == 0)
                    return $table;
        return null;
    }

    private function isTableExist($tableName) {
        return $this->getTable($tableName)? true: false;
    }

    //
    //  Prida tabulku pro ulozeni hodnoty
    private function addValueTable($tableName, $dataType, $parentName = null) {

        if ( ! $this->isTableExist($tableName)) {
            if ($dataType == COLOUMN_DATATYPE::NVARCHAR_D)
                $dataType = COLOUMN_DATATYPE::NTEXT_D;
            return $this->addTable($tableName, $parentName)->addColoumn("value", 0, false)->setDataType($dataType);
        }

        if ( ! $this->getTable($tableName)->coloumnExist("value")) {
            if ($dataType == COLOUMN_DATATYPE::NVARCHAR_D)
                $dataType = COLOUMN_DATATYPE::NTEXT_D;
            return $this->getTable($tableName)->setParentName($parentName)->addColoumn("value", 0, false)->setDataType($dataType);
        }

        return $this->getTable($tableName);
    }




    //
    //  DEBUG funkce
    //
    public function printTables() {

        foreach ($this->tables as $table) {
            echo "\nT: " . $table->getname(). "  P:".$table->getParentname();
            if ($table->getColumns() != null)
                foreach ($table->getColumns() as $column) {
                    echo "\n\tC: " . $column->getNameForDb() . " ". "(". $column->getDataTypeForDB() .") = " . $table->numOfColoumnsLike($column->getName());
                }
        }
        echo "\n";
    }

    public function printTablesDB() {

        foreach ($this->tables as $table) {
            echo "\nT: " . $table->getname(). "  P:".$table->getParentname();
            if ($table->getColoumnsForDB() != null)
                foreach ($table->getColoumnsForDB() as $column) {
                    echo "\n\tC: " . $column->getNameForDb() . " ". "(". $column->getDataTypeForDB() .") = " . $table->numOfColoumnsLike($column->getName());
                }
        }
        echo "\n";
    }

    public function printTablesRelations() {
        foreach ($this->tables as $table) {
            echo "\nT: " . $table->getname(). "  P:".$table->getParentname();
            foreach ($this->tables as $tableR) {
                echo "\n\tT: " . $tableR->getname(). " = ". $table->getRelation($tableR->getname());
            }
        }
        echo "\n";
    }

    public function print_d($string) {
        if ($this->DEBUG)
            echo $string;
    }

}