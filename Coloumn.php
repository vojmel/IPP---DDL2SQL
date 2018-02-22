<?php
//
//  Trida Coloumn
//  Predstavuje jeden sloupec tabulky
//
//  Vojtech Meluzin
//  xmeluz04
//

abstract class COLOUMN_DATATYPE
{
    const BIT_D 		= 0;
    const INT_D 		= 1;
    const FLOAT_D 		= 2;
    const NVARCHAR_D 	= 3;
    const NTEXT_D 		= 4;
};


class Coloumn
{
	
	private $name;
	private $dataType;
	private $addId;           // bude se priat ke jmenu id?
    private $createValTable;  // bude se vytvaret value tabulka?
    private $isPK;            // je private key

    private $canDelete = true;// soupec se nesmi odstranit - je to cizi klic


	function __construct($name_s, $valueOfColoumn, $addId = false, $createValTable = false, $isPK = false) {

		$this->name 		= $name_s;
		$this->dataType 	= Coloumn::getDataTypeName($valueOfColoumn);
		$this->addId        = $addId;
		$this->createValTable = $createValTable;
		$this->isPK         = $isPK;

		return $this;
	}

	public function setDataType($dataType_i) {

	    if ($dataType_i != null)
		    $this->dataType = ($dataType_i > $this->dataType)? $dataType_i: $this->dataType;

		return $this;
	}

    public function setDataTypeValue($dataType_v) {

        $this->setDataType(Coloumn::getDataTypeName($dataType_v));
        return $this;
    }

	public function setValueDatatype($value_s) {
	    $this->dataType = Coloumn::getDataTypeName($value_s);
	    return $this;
    }


    /**
     * @param bool $cantDelete
     */
    public function setCantDelete($cantDelete)
    {
        $this->canDelete = $cantDelete;
        return $this;
    }


    /**
     * @return bool
     */
    public function isCanDelete()
    {
        return $this->canDelete;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $addId
     */
    public function setAddId($addId)
    {
        $this->addId = $addId;
        return $this;
    }

    /**
     * @param bool $createValTable
     */
    public function setCreateValTable($createValTable)
    {
        $this->createValTable = $createValTable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCreateValTable()
    {
        return $this->createValTable;
    }

    /**
     * @return bool
     */
    public function isPK()
    {
        return $this->isPK;
    }

    /**
     * @return bool
     */
    public function isAddId()
    {
        return $this->addId;
    }



    // Nazav sloupce bez poradoveho cisla sloupce
    public function getNameWithNoRank() {
        $retVal = $this->name;
        // odstraneni _[0-9]
        if (preg_match("/.+[0-9]+$/", $retVal))
            $retVal = preg_replace("/[0-9]+$/", "", $retVal);
        return $retVal;
    }

    public function getNameForDb()
    {
        return $this->name. (($this->addId)? "_id": "");
    }

    public function getDataTypeForDB()
    {
        if ($this->addId)
            return "INT";

        switch ($this->dataType) {
            case 0:
                return "BIT";
            case 1:
                return "INT";
            case 2:
                return "FLOAT";
            case 3:
                return "NVARCHAR";
            case 4:
                return "NTEXT";
        }
    }


    public static function getDataTypeName( $value ) {

        $value = trim($value);

        if ($value == null)
            return COLOUMN_DATATYPE::BIT_D;
        if ($value == "")
            return COLOUMN_DATATYPE::BIT_D;
        if (($value == '0') || ($value == '1'))
            return COLOUMN_DATATYPE::BIT_D;
        if ((strcasecmp(strtoupper($value), "TRUE") == 0) || (strcasecmp(strtoupper($value),"FALSE") == 0))
            return COLOUMN_DATATYPE::BIT_D;

        // cisla
        if (is_numeric($value)) {

            $int = (int)$value;

            if(strrpos($value, ".")
                or strrpos($value, "f")
                or strrpos($value, "F")
                or strrpos($value, "e")
                or strrpos($value, "E"))
            {
                return COLOUMN_DATATYPE::FLOAT_D;
            }
            else
            {
                if (($int == 0) || ($int == 1))
                    return COLOUMN_DATATYPE::BIT_D;
            }

            return COLOUMN_DATATYPE::INT_D;
        }

        if (strcmp(intval($value), $value) == 0)
            return COLOUMN_DATATYPE::INT_D;

        if (strcmp(floatval($value), $value) == 0)
            return COLOUMN_DATATYPE::FLOAT_D;


        return COLOUMN_DATATYPE::NVARCHAR_D;
    }
}