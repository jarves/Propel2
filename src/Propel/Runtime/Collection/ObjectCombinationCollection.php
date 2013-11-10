<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Collection\Exception\UnsupportedRelationException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

/**
 * Class for iterating over a list of Propel objects
 *
 * @author Francois Zaninotto
 */
class ObjectCombinationCollection extends ObjectCollection
{

    /**
     * Get an array of the primary keys of all the objects in the collection
     *
     * @param  boolean $usePrefix
     * @return array   The list of the primary keys of the collection
     */
    public function getPrimaryKeys($usePrefix = true)
    {
        $ret = array();

        /** @var $obj ActiveRecordInterface */
        foreach ($this as $combination) {
            $pkCombo = [];
            foreach ($combination as $key => $obj) {
                $pkCombo[$key] = $obj->getPrimaryKey();
            }
            $ret[] = $pkCombo;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function push($value)
    {
        parent::push(func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function search($element)
    {
        $hashes = [];
        $isActiveRecord = [];
        foreach (func_get_args() as $pos => $obj) {
            if (is_object($obj) && $obj instanceof ActiveRecordInterface) {
                $hashes[$pos] = $obj->hashCode();
                $isActiveRecord[$pos] = true;
            } else {
                $hashes[$pos] = $obj;
                $isActiveRecord[$pos] = false;
            }
        }
        foreach ($this as $pos => $combination) {
            $found = true;
            foreach ($combination as $idx => $obj) {
                if (null === $obj) {
                    if ($obj !== $hashes[$idx]) {
                        $found = false;
                        break;
                    }
                } else if ($isActiveRecord[$idx] ? $obj->hashCode() !== $hashes[$idx] : $obj !== $hashes[$idx]) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                return $pos;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeObject($element)
    {
        if (false !== ($pos = call_user_func_array([$this, 'search'], func_get_args()))) {
            $this->remove($pos);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        return false !== call_user_func_array([$this, 'search'], func_get_args());
    }

}
