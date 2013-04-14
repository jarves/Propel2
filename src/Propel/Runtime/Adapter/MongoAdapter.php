<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\MongoConnection;
use Propel\Runtime\Map\ColumnMap;

/**
 * This is used in order to connect to a MySQL database.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jon S. Stevens <jon@clearink.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
class MongoAdapter implements AdapterInterface
{
    /**
     * Build database connection
     *
     * @param array $conparams connection parameters
     *
     * @return Propel\Runtime\Connection\ConnectionInterface
     */
    public function getConnection($conparams)
    {
        $client = new MongoConnection('mongodb://'.$conparams['server'],
            $conparams['database'],
            $conparams['options']
        );

        return $client;
    }

    /**
     * Sets the character encoding using SQL standard SET NAMES statement.
     *
     * This method is invoked from the default initConnection() method and must
     * be overridden for an RDMBS which does _not_ support this SQL standard.
     *
     * @see initConnection()
     *
     * @param Propel\Runtime\Connection\ConnectionInterface $con
     * @param string $charset The $string charset encoding.
     */
    public function setCharset(ConnectionInterface $con, $charset)
    {
        // TODO: Implement setCharset() method.
    }

    /**
     * This method is used to ignore case.
     *
     * @param  string $in The string to transform to upper case.
     * @return string The upper case string.
     */
    public function toUpperCase($in)
    {
        // TODO: Implement toUpperCase() method.
    }

    /**
     * This method is used to ignore case.
     *
     * @param  string $in The string whose case to ignore.
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCase($in)
    {
        // TODO: Implement ignoreCase() method.
    }

    /**
     * This method is used to ignore case in an ORDER BY clause.
     * Usually it is the same as ignoreCase, but some databases
     * (Interbase for example) does not use the same SQL in ORDER BY
     * and other clauses.
     *
     * @param  string $in The string whose case to ignore.
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCaseInOrderBy($in)
    {
        // TODO: Implement ignoreCaseInOrderBy() method.
    }

    /**
     * Returns the character used to indicate the beginning and end of
     * a piece of text used in a SQL statement (generally a single
     * quote).
     *
     * @return string The text delimiter.
     */
    public function getStringDelimiter()
    {
        // TODO: Implement getStringDelimiter() method.
    }

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param string $s1 String to concatenate.
     * @param string $s2 String to append.
     *
     * @return string
     */
    public function concatString($s1, $s2)
    {
        // TODO: Implement concatString() method.
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param string $s   String to extract from.
     * @param integer $pos Offset to start from.
     * @param integer $len Number of characters to extract.
     *
     * @return string
     */
    public function subString($s, $pos, $len)
    {
        // TODO: Implement subString() method.
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param  string $s String to calculate length of.
     * @return string
     */
    public function strLength($s)
    {
        // TODO: Implement strLength() method.
    }

    /**
     * Quotes database object identifiers (table names, col names, sequences, etc.).
     * @param  string $text The identifier to quote.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($text)
    {
        // TODO: Implement quoteIdentifier() method.
    }

    /**
     * Quotes a database table which could have space separating it from an alias,
     * both should be identified separately. This doesn't take care of dots which
     * separate schema names from table names. Adapters for RDBMs which support
     * schemas have to implement that in the platform-specific way.
     *
     * @param  string $table The table name to quo
     * @return string The quoted table name
     **/
    public function quoteIdentifierTable($table)
    {
        // TODO: Implement quoteIdentifierTable() method.
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return boolean
     */
    public function isGetIdBeforeInsert()
    {
        // TODO: Implement isGetIdBeforeInsert() method.
    }

    /**
     * Whether this adapter uses an ID generation system that requires getting ID _before_ performing INSERT.
     *
     * @return boolean
     */
    public function isGetIdAfterInsert()
    {
        // TODO: Implement isGetIdAfterInsert() method.
    }

    /**
     * Gets the generated ID (either last ID for autoincrement or next sequence ID).
     *
     * @param Propel\Runtime\Connection\ConnectionInterface $con
     * @param string $name
     *
     * @return mixed
     */
    public function getId(ConnectionInterface $con, $name = null)
    {
        // TODO: Implement getId() method.
    }

    /**
     * Formats a temporal value before binding, given a ColumnMap object
     *
     * @param mixed $value The temporal value
     * @param Propel\Runtime\Map\ColumnMap $cMap
     *
     * @return string The formatted temporal value
     */
    public function formatTemporalValue($value, ColumnMap $cMap)
    {
        // TODO: Implement formatTemporalValue() method.
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimestampFormatter()
    {
        // TODO: Implement getTimestampFormatter() method.
    }

    /**
     * Returns date formatter string for use in date() function.
     *
     * @return string
     */
    public function getDateFormatter()
    {
        // TODO: Implement getDateFormatter() method.
    }

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimeFormatter()
    {
        // TODO: Implement getTimeFormatter() method.
    }

    /**
     * Should Column-Names get identifiers for inserts or updates.
     * By default false is returned -> backwards compatibility.
     *
     * it`s a workaround...!!!
     *
     * @todo       should be abstract
     * @deprecated
     *
     * @return boolean
     */
    public function useQuoteIdentifier()
    {
        // TODO: Implement useQuoteIdentifier() method.
    }

}