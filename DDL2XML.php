<?php
//
//  Vytvareni vystupnich stringu
//
//  Vojtech Meluzin
//  xmeluz04
//

include_once "Table.php";
include_once "Coloumn.php";


class DDL2XML
{
    private $out;           // vystupni string
    private $tables;           // vstupni DDL

    //
    //  Vstupem je vystupni objekt z XML2DDL
    function __construct($tables)
    {
        $this->tables = $tables;
        return $this;
    }


    //
    //  Vytvoreni vzstupu jako DDL tabulek
    public function make() {

        $tablesSQL = "";

        if ($this->tables != null)
        foreach ($this->tables as $table) {

            $tableCreate = "CREATE TABLE ".$table->getName()."(\n";

            $lastColoumn = end($table->getColoumnsForDB());
            foreach ($table->getColoumnsForDB() as $coloumn) {
                $tableCreate .= "\t". $coloumn->getNameForDb()." ".
                    $coloumn->getDataTypeForDB().
                    ($coloumn->isPK()? " PRIMARY KEY":"");

                if ($coloumn != $lastColoumn)
                    $tableCreate .=",";

                $tableCreate .= "\n";
            }

            $tableCreate .= ");\n\n";
            $tablesSQL .= $tableCreate;
        }

        $this->out = $tablesSQL;

        return $this;
    }


    //
    //  Vytvoreni vystupu jako XML relaci
    //  Predpoklada jiz nalezene relace
    public function makeRelations() {

        $output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tables>";


        foreach ($this->tables as $table) {

            $tableSQl = "\n\t<table name=\"".$table->getname()."\">\n";
            foreach ($this->tables as $tableR) {
                $relation = $table->getRelation($tableR->getname());
                if ($relation != null)
                    $tableSQl .= "\t\t<relation to=\"".$tableR->getname()."\" relation_type=\"".$relation."\" />\n";
            }
            $tableSQl .= "\t</table>";
            $output .=$tableSQl;
        }

        $output .= "\n</tables>\n";

        $this->out = $output;
        return $this;
    }


    public function getOutPut() {
        return $this->out;
    }
}