<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Convert entire database to utf-8
 *
 * @codingStandardsIgnoreFile
 *
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Mark Ma <mark.ma@reyosoft.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version99 extends Doctrine_Migration_Base
{

    /**
     * convert database and its tables and columns to utf-8 
     * 
     * @return void
     */
    public function up()
    {
        $dbh = Doctrine_Manager::getInstance()->connection()->getDbh();
        $schema = Fisma::$appConf['db']['schema'];

        Doctrine_Manager::connection()->commit();
        $dbh->query("ALTER DATABASE $schema CHARACTER SET utf8");

        $tableSql = "SELECT table_name FROM information_schema.tables WHERE table_schema like '$schema'";

        $tables = $dbh->query($tableSql)->fetchAll();

        // change each table to utf-8
        for ($i = 0; $i < count($tables); $i++) {
            $changeTableSql = "ALTER TABLE " . $tables[$i]['table_name'] . " CHARACTER SET utf8";
            $dbh->query($changeTableSql);
        }
  
        // the column types include text, tinytext, mediumtext, longtext, varchar, char, enum 
        // need to convert ot utf-8
        $colInfoSql = "SELECT column_name, table_name, column_type,column_default,is_nullable 
                         FROM information_schema.columns 
                         WHERE table_schema like '$schema' 
                         AND (column_type in ('text','tinytext','mediumtext','longtext') 
                         OR column_type like 'varchar%' 
                         OR column_type like 'char%' 
                         OR column_type like 'enum%')";
        $columns = $dbh->query($colInfoSql)->fetchAll();

        for ($i = 0; $i < count($columns); $i++) {
            $convertSqls = $this->_constructConvertSql( $columns[$i]['column_name'], 
                                                        $columns[$i]['column_type'], 
                                                        $columns[$i]['table_name']);

            for ($n =0; $n < count($convertSqls); $n++) {
                $dbh->query($convertSqls[$n]);
            }
        } 
    }

    /**
     * Irreversible
     * 
     * @return void
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }

    /**
     * construct sqls for converting columns to utf8 
     * 
     * @return array contains sqls 
     */
    private function _constructConvertSql($columnName, 
                                          $colunmType, 
                                          $tableName) {

        // Audit log uses Fisma_String::htmlToPlainText in which it uses 
        // iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $html) before save data to DB. So, the stored data in 
        // audit log table are already UTF-8 encoded. To convert UTF-8 encoded data, it needs to use 
        // following two sqls in sequence. 
        $queries = array();
        if (strtolower(substr($tableName,-4)) == '_log') {
            $queries[] = "ALTER table $tableName CHANGE $columnName $columnName $colunmType CHARACTER SET BINARY"; 
            $queries[] = "ALTER table $tableName CHANGE $columnName $columnName $colunmType CHARACTER SET utf8"; 
        } else {
            $queries[] = "ALTER table $tableName CHANGE $columnName $columnName $colunmType CHARACTER SET utf8"; 
        }
        return $queries;
    }
}
