<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Util\BasePeer;
use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Connection\StatementInterface;

use \PDO;

/**
 * Oracle adapter.
 *
 * @author     David Giffin <david@giffin.org> (Propel)
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Jon S. Stevens <jon@clearink.com> (Torque)
 * @author     Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author     Bill Schneider <bschneider@vecna.com> (Torque)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version    $Revision$
 * @package    propel.runtime.adapter
 */
class OracleAdapter extends AbstractAdapter
{
    /**
     * This method is called after a connection was created to run necessary
     * post-initialization queries or code.
     * Removes the charset query and adds the date queries
     *
     * @see       parent::initConnection()
     *
     * @param     \Propel\Runtime\Connection\ConnectionPdo $con
     * @param     array $settings  A $PDO PDO connection instance
     */
    public function initConnection($con, array $settings)
    {
        $con->exec("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD'");
        $con->exec("ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'");
        if (isset($settings['queries']) && is_array($settings['queries'])) {
            foreach ($settings['queries'] as $queries) {
                foreach ((array)$queries as $query) {
                    $con->exec($query);
                }
            }
        }
    }

    /**
     * This method is used to ignore case.
     *
     * @param     string  $in  The string to transform to upper case.
     * @return    string  The upper case string.
     */
    public function toUpperCase($in)
    {
        return "UPPER(" . $in . ")";
    }

    /**
     * This method is used to ignore case.
     *
     * @param     string  $in  The string whose case to ignore.
     * @return    string  The string in a case that can be ignored.
     */
    public function ignoreCase($in)
    {
        return "UPPER(" . $in . ")";
    }

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param     string  $s1  String to concatenate.
     * @param     string  $s2  String to append.
     *
     * @return    string
     */
    public function concatString($s1, $s2)
    {
        return "CONCAT($s1, $s2)";
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param     string   $s  String to extract from.
     * @param     integer  $pos  Offset to start from.
     * @param     integer  $len  Number of characters to extract.
     *
     * @return    string
     */
    public function subString($s, $pos, $len)
    {
        return "SUBSTR($s, $pos, $len)";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param     string  $s  String to calculate length of.
     * @return    string
     */
    public function strLength($s)
    {
        return "LENGTH($s)";
    }

    /**
     * @see       AbstractAdapter::applyLimit()
     *
     * @param     string   $sql
     * @param     integer  $offset
     * @param     integer  $limit
     * @param     null|Criteria  $criteria
     */
    public function applyLimit(&$sql, $offset, $limit, $criteria = null)
    {
        if (BasePeer::needsSelectAliases($criteria)) {
            $crit = clone $criteria;
            $selectSql = $this->createSelectSqlPart($crit, $params, true);
            $sql = $selectSql . substr($sql, strpos($sql, 'FROM') - 1);
        }
        $sql = 'SELECT B.* FROM ('
            . 'SELECT A.*, rownum AS PROPEL_ROWNUM FROM (' . $sql . ') A '
            . ') B WHERE ';

        if ( $offset > 0 ) {
            $sql .= ' B.PROPEL_ROWNUM > ' . $offset;
            if ( $limit > 0 ) {
                $sql .= ' AND B.PROPEL_ROWNUM <= ' . ( $offset + $limit );
            }
        } else {
            $sql .= ' B.PROPEL_ROWNUM <= ' . $limit;
        }
    }

    /**
     * @return int
     */
    protected function getIdMethod()
    {
        return AbstractAdapter::ID_METHOD_SEQUENCE;
    }

    /**
     * @param     ConnectionInterface $con
     * @param     string $name
     *
     * @throws    PropelException
     * @return    integer
     */
    public function getId($con, $name = null)
    {
        if ($name === null) {
            throw new PropelException("Unable to fetch next sequence ID without sequence name.");
        }

        $stmt = $con->query("SELECT " . $name . ".nextval FROM dual");
        $row = $stmt->fetch(PDO::FETCH_NUM);

        return $row[0];
    }

    /**
     * @param     string  $seed
     * @return    string
     */
    public function random($seed=NULL)
    {
        return 'dbms_random.value';
    }

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param     Criteria  $criteria
     * @return    Criteria  The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria)
    {
        $selectColumns = $criteria->getSelectColumns();
        // clearSelectColumns also clears the aliases, so get them too
        $asColumns = $criteria->getAsColumns();
        $criteria->clearSelectColumns();
        $columnAliases = $asColumns;
        // add the select columns back
        foreach ($selectColumns as $id => $clause) {
            // Generate a unique alias
            $baseAlias = "ORA_COL_ALIAS_".$id;
            $alias = $baseAlias;
            // If it already exists, add a unique suffix
            $i = 0;
            while (isset($columnAliases[$alias])) {
                $i++;
                $alias = $baseAlias . '_' . $i;
            }
            // Add it as an alias
            $criteria->addAsColumn($alias, $clause);
            $columnAliases[$alias] = $clause;
        }
        // Add the aliases back, don't modify them
        foreach ($asColumns as $name => $clause) {
            $criteria->addAsColumn($name, $clause);
        }

        return $criteria;
    }

    /**
     * @see       AbstractAdapter::bindValue()
     *
     * @param     PDOStatement  $stmt
     * @param     string        $parameter
     * @param     mixed         $value
     * @param     ColumnMap     $cMap
     * @param     null|integer  $position
     *
     * @return    boolean
     */
    public function bindValue(StatementInterface $stmt, $parameter, $value, ColumnMap $cMap, $position = null)
    {
        if ($cMap->isTemporal()) {
            $value = $this->formatTemporalValue($value, $cMap);
        } elseif ($cMap->getType() == PropelColumnTypes::CLOB_EMU) {
            return $stmt->bindParam(':p'.$position, $value, $cMap->getPdoType(), strlen($value));
        } elseif (is_resource($value) && $cMap->isLob()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            rewind($value);
        }

        return $stmt->bindValue($parameter, $value, $cMap->getPdoType());
    }
}
