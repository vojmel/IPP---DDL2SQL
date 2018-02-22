<?php
//
//  Trida Table
//  Vytvari a obsluhuje sloupecky a uchovava informace o jedne tabulce
//
//  Vojtech Meluzin
//  xmeluz04
//

include_once "Coloumn.php";

class Table
{
    private $columns;               // sloupce tabulky

    private $name;                  // nazev tabulky
    private $parentName = null;     // nazev rodice - maji pouze

    private $relations = null;      // Relace na ostatni tabulky


    public function __construct( $name = null, $parent = null) {

        if ($name == null)
            return null;

        $this->name = $name;
        $this->parentName = $parent;

        return $this;
    }

    // Hleda sloupecek se stejnym nazvem, ale jinacim poradovym cislem
    // room1  najde  room2
    public function findColoumnLike($coloumnName = null) {
        if ($coloumnName != null)
            if (count($this->columns) > 0) {
                    foreach ($this->columns as $coloumn)
                        if ($coloumn instanceof Coloumn)
                            if ($coloumn->isAddId()) // jen neatributove
                            if (preg_match("/^(" . $coloumnName . ")[0-9]*$/", $coloumn->getName()))
                                return $coloumn;
            }
        return null;
    }


    public function coloumnExistLike( $coloumnName = null ) {

        if ($this->findColoumnLike($coloumnName) == null)
            return false;
        return true;
    }


    // Kolikrat se opakuje sloupecek se stejnym nazvem, ale jinacim poradovym cislem
    public function numOfColoumnsLike($coloumnName) {
        return count($this->getAllColoumnsLike($coloumnName));
    }


    // Vsechny slopce, ktere maji stejny nazev, ale jinaci poradove cislo
    public function getAllColoumnsLike($coloumnName) {

        $resColoumns = Array();
        if ($coloumnName != null) {

            // odstraneni _[0-9]
            if (preg_match("/^.+[0-9]+$/", $coloumnName)) {
                $coloumnName = preg_replace("/[0-9]+$/", "", $coloumnName);
            }

            if (count($this->columns) > 0)
                foreach ($this->columns as $coloumn)
                    if ($coloumn instanceof Coloumn)
                        if (preg_match("/^(".$coloumnName . ")[0-9]*$/", $coloumn->getName()))
                            $resColoumns[] = $coloumn;
        }


        return (count($resColoumns) > 0)? $resColoumns: null;
    }

    // Vsechny slopce, ktere maji stejny nazev, ale jinaci poradove cislo, a nejsou attribut
    public function getAllColoumnsNotAttrLike($coloumnName) {

        $resColoumns = Array();
        if ($coloumnName != null) {

            // odstraneni _[0-9]
            if (preg_match("/^.+[0-9]+$/", $coloumnName)) {
                $coloumnName = preg_replace("/[0-9]+$/", "", $coloumnName);
            }

            if (count($this->columns) > 0)
                foreach ($this->columns as $coloumn)
                    if ($coloumn instanceof Coloumn)
                        if (preg_match("/^(".$coloumnName . ")[0-9]*$/", $coloumn->getName()))
                            if ($coloumn->isAddId())
                                $resColoumns[] = $coloumn;
        }


        return (count($resColoumns) > 0)? $resColoumns: null;
    }


    // Vsem sloupcu s podobnym nazvem nastavi datovy typ
    public function setDataTypeToAllColoumnsLike($name, $value) {

        $columns = $this->getAllColoumnsLike($name);
        if ($columns != null)
            foreach ($columns as $column) {
                $column->setDataTypeValue($value);
            }
    }


    // Odstrani vsechny slopce, ktere maji stejny nazev, ale jinaci poradove cislo
    public function removeAllColoumnsLike($coloumnName) {

        $coloumnsToRemove = $this->getAllColoumnsLike($coloumnName);
        if ($coloumnsToRemove != null)
            foreach ($coloumnsToRemove as $coloumn)
                $this->removeColoumn($coloumn);

        return $this;
    }

    public function findColoumn($coloumnName = null, $withID = false) {
        if ($coloumnName != null)
            if (count($this->columns) > 0)
                foreach ($this->columns as $coloumn)
                    if ($coloumn instanceof Coloumn) {
                        if ($withID) // chceme testovat jen neatributove
                            if ( ! $coloumn->isAddId())
                                continue;
                        if (strcmp($coloumn->getName(), $coloumnName) == 0)
                            return $coloumn;
                    }
        return null;
    }


    public function findColoumnDB($coloumnName = null) {
        if ($coloumnName != null)
            if (count($this->columns) > 0)
                foreach ($this->columns as $coloumn)
                    if ($coloumn instanceof Coloumn)
                        if (strcmp ($coloumn->getNameForDb(), $coloumnName) == 0)
                            return $coloumn;
        return null;
    }


    public function coloumnExist( $coloumnName = null, $withId = false ) {

        if ($this->findColoumn($coloumnName, $withId) == null)
            return false;
        return true;
    }


    public function findColoumnAttr($coloumnName = null) {
        if ($coloumnName != null)
            if (count($this->columns) > 0)
                foreach ($this->columns as $coloumn)
                    if ($coloumn instanceof Coloumn) {
                        if ( ! $coloumn->isAddId()) // chceme jen attributy
                        if (strcmp($coloumn->getName(), $coloumnName) == 0)
                            return $coloumn;
                    }
        return null;
    }



    public function coloumnAttrExist($coloumnName = null) {
        if ($this->findColoumnAttr($coloumnName) == null)
            return false;
        return true;
    }


    public function addColoumn( $coloumnName = null, $coloumnValue = null, $addId = false, $createValTable = false ) {

        if ($coloumnName != null) {
            if ( ! $this->coloumnExist($coloumnName, $addId)) {
                $newColoumn = new Coloumn($coloumnName, $coloumnValue, $addId, $createValTable);
                $this->columns[] = $newColoumn;
                return end($this->columns);
            } else {

                // Hledame pouze mezi attributovejma
                if ( ! $addId) {
                    $coloumnToUpdate = $this->findColoumnDB($coloumnName);
                    if ($coloumnToUpdate != null)
                    return $coloumnToUpdate->setDataTypeValue($coloumnValue);
                }

                return $this->findColoumn($coloumnName, $addId)->setDataTypeValue($coloumnValue);
            }
        }

        return null;
    }

    public function setColoumnName( $oldName, $newName) {
        $coloumn = $this->findColoumn($oldName);
        if ($coloumn != null)
            return $coloumn->setName($newName);
        return null;
    }


    public function removeColoumn( $coloumnName )
    {

        if ($coloumnName instanceof Coloumn)
            $coloumnName = $coloumnName->getName();

        if ($this->coloumnExist($coloumnName))
            foreach ($this->columns as $key => $val)
                if (strcmp($val->getName(), $coloumnName) == 0)
                    unset($this->columns[$key]);
    }


    /**
     * @return null
     */
    public function getParentName()
    {
        return $this->parentName;
    }

    /**
     * @param null $parentName
     */
    public function setParentName($parentName)
    {
        if ($parentName != null)
            $this->parentName = $parentName;
        return $this;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }


    public function getColoumnsForDB() {

        if (count($this->columns) == 0)
            return array((new Coloumn("prk_".$this->name, 10, true, false, true)));
        return array_merge(
            // Pridani prk id sloupecku
            [(new Coloumn("prk_".$this->name, 10, true, false, true))],
            $this->columns);
    }


    //
    //  Relace pomocne funkce
    //
    public function setRelationTables($tables) {
        if ($this->relations != null)
            $this->relations = array_merge($this->relations, $tables);
        else
            $this->relations = $tables;
    }

    /**
     * @return null
     */
    public function getRelations()
    {
        return $this->relations;
    }

    public function setRelation($tableName, $relace) {
        if (array_key_exists($tableName, $this->relations))
            $this->relations[$tableName] = $relace;
    }

    public function getRelation($tableName) {
        if (array_key_exists($tableName, $this->relations))
            return $this->relations[$tableName];
        return null;
    }

    public function setRelationNadrezenaK($tableName) {
        if (array_key_exists($tableName, $this->relations))
            $this->relations[$tableName] = "N:1";
    }

    public function setRelationPodrezenaK($tableName) {
        if (array_key_exists($tableName, $this->relations))
            $this->relations[$tableName] = "1:N";
    }

    public function setRelationRovna($tableName) {
        if (array_key_exists($tableName, $this->relations))
            $this->relations[$tableName] = "1:1";
    }
    public function setRelationNKuM($tableName) {
        if (array_key_exists($tableName, $this->relations))
            $this->relations[$tableName] = "N:M";
    }

    public function isNekomuNadrazena() {

        foreach ($this->relations as $relation)
            if ($relation == "N:1")
                return true;

        return false;
    }

    public function noRelationsSetted() {

        foreach ($this->relations as $relation)
            if ($relation != null)
                return false;

        return true;
    }

    public function isNekomuPodrazena() {
        foreach ($this->relations as $relation)
            if ($relation == "1:N")
                return true;
        return false;
    }

    public function getRelationsNadrazeneK() {
        $ret = array();
        foreach ($this->relations as $key => $relation)
            if ($relation == "N:1")
                $ret[] = $key;
        return $ret;
    }

    public function getRelationsPodrazeneK() {
        $ret = array();
        foreach ($this->relations as $key => $relation)
            if ($relation == "1:N")
                $ret[] = $key;
        return $ret;
    }

    public function getNotSettedRelations() {
        $ret = array();
        foreach ($this->relations as $key => $relation)
            if ($relation == null)
                $ret[] = $key;
        return $ret;
    }

    public function hasAllRelations() {
        foreach ($this->relations as $relation)
            if ($relation == null)
                return false;

        return true;
    }

    public function isNadrazenK($tableName) {
        if (array_key_exists($tableName, $this->relations))
            if ($this->relations[$tableName] == "N:1")
                return true;
        return false;
    }

}