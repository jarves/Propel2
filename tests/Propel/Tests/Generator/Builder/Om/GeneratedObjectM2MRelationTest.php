<?php

namespace Propel\Tests\Issues;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\Helpers\PlatformDatabaseBuildTimeBase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel/issues/617.
 * Since the build property `addVendorInfo` is per default not set (= false), the `MysqlSchemaParser` **did**
 * not return the `Engine` of the table. Since we depend on that information in `MysqlPlatform`,
 * we really need that kind of information.
 *
 */
class GeneratedObjectM2MRelationTest extends PlatformDatabaseBuildTimeBase
{
    protected $databaseName = 'migration';

    /**
     * @group test1
     */
    public function testSimpleRelation()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation1_user_friend" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="friend_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation1_user" phpName="Who">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation1_user" phpName="Friend">
                <reference local="friend_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation1_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name"/>
        </table>
    </database>
        ';

        $this->buildAndMigrate($schema);

        /**
         *
         *               addFriend | removeFriend | setFriends | getFriends
         *              +---------------------------------------------------
         * addFriend    |    1             2             3            4
         * removeFriend |    5             6             7            8
         * setFriends   |    9            10            11           12
         *
         */


        /*
         * ####################################
         * 1. addFriend, addFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friends');

        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');


        /*
         * ####################################
         * 2. addFriend, removeFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertEquals($hans, current($friend1->getWhos()), 'Hans is friend1\'s friend.');
        $this->assertEquals($hans, current($friend2->getWhos()), 'Hans is friend2\'s friend.');
        $hans->save();
        $this->assertCount(1, $friend1->getWhos(), 'Friend 1 is from one guy (hans) a friend');
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

        $hans->removeFriend($friend1);
        $this->assertCount(0, $friend1->getWhos(), 'Friend 1 is from nobody a friend');
        $this->assertEquals($hans, current($friend2->getWhos()), 'Hans is friend2\'s friend.');
        $this->assertCount(1, $hans->getFriends(), 'one friends');

        $hans->save();
        $this->assertCount(0, $friend1->getWhos(), 'Friend 1 is from nobody a friend');
        $this->assertEquals($hans, current($friend2->getWhos()), 'Hans is friend2\'s friend.');

        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 2 as friend');


        /*
         * ####################################
         * 3. addFriend, setFriends
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');

        $friends = new ObjectCollection();
        $friends[] = $friend1;
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');


        /*
         * ####################################
         * 4. addFriend, getFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertCount(1, $hans->getFriends());


        /*
         * ####################################
         * 5. removeFriend, addFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');
        $this->assertCount(1, $hans->getFriends());


        //db prepared.
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->removeFriend($friend1);
        $this->assertCount(0, $hans->getFriends());
        $hans->addFriend($friend2);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 2 as friend');


        //same with new instances.
        \Map\Relation1UserTableMap::clearInstancePool();
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');
        $friend2 = \Relation1UserQuery::create()->findOneByName('Friend 2');

        $this->assertSame($friend2, current($newHansObject->getFriends()));
        $newHansObject->removeFriend($friend2);
        $this->assertCount(0, $newHansObject->getFriends());
        $newHansObject->save();

        $newHansObject->addFriend($friend1);
        $this->assertCount(1, $newHansObject->getFriends());

        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($newHansObject)->findOne(), 'Hans has Friend 2 as friend');


        /*
         * ####################################
         * 6. removeFriend, removeFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends());
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertCount(2, $hans->getFriends());

        //db prepared, work now with new objects.
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');
        $friend2 = \Relation1UserQuery::create()->findOneByName('Friend 2');

        $newHansObject->removeFriend($friend1);
        $this->assertCount(1, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($newHansObject)->findOne(), 'Hans has Friend 2 as friend');


        $newHansObject->removeFriend($friend2);
        $this->assertCount(0, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(0, \Relation1UserFriendQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(0, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has zero friends.');


        /*
         * ####################################
         * 7. removeFriend, setFriends
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');
        $this->assertCount(1, $hans->getFriends());

        //db prepared, work now with new objects.
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');

        $newHansObject->removeFriend($friend1);
        $this->assertCount(0, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(0, \Relation1UserFriendQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(0, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has zero friends.');

        $friends = new ObjectCollection();
        $friends[] = $friend1;
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $newHansObject->setFriends($friends);
        $this->assertCount(2, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has two friends.');


        /*
         * ####################################
         * 8. removeFriend, getFriends
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends());
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertCount(2, $hans->getFriends());

        //db prepared, work now with new objects.
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');
        $friend2 = \Relation1UserQuery::create()->findOneByName('Friend 2');

        $this->assertCount(2, $newHansObject->getFriends());
        $newHansObject->removeFriend($friend1);
        $this->assertCount(1, $newHansObject->getFriends());
        $this->assertEquals($friend2, current($newHansObject->getFriends()));
        $newHansObject->save();

        \Map\Relation1UserTableMap::clearInstancePool();
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $this->assertCount(1, $newHansObject->getFriends());

        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has one friend.');
        $this->assertEquals('Friend 2', \Relation1UserQuery::create()->filterByWho($newHansObject)->findOne()->getName(), 'Hans has Friend 2 as friend');


        /*
         * ####################################
         * 9. setFriends, addFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

        $friend3 = (new \Relation1User())->setName('Friend 3');
        $hans->addFriend($friend3);
        $hans->save();
        $this->assertEquals(4, \Relation1UserQuery::create()->count(), 'We have four users.');
        $this->assertEquals(3, \Relation1UserFriendQuery::create()->count(), 'We have three connections.');
        $this->assertEquals(3, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has three friends.');;


        /*
         * ####################################
         * 10. setFriends, removeFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

        $hans->removeFriend($friend1);
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');


        /*
         * ####################################
         * 11. setFriends, setFriends
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals($friends, \Relation1UserQuery::create()->filterByWho($hans)->find());


        $friends = new ObjectCollection();
        $friends[] = $friend3 = (new \Relation1User())->setName('Friend 3');
        $friends[] = $friend4 = (new \Relation1User())->setName('Friend 4');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(5, \Relation1UserQuery::create()->count(), 'We have five users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals($friends, \Relation1UserQuery::create()->filterByWho($hans)->find());


        /*
         * ####################################
         * 11. setFriends, setFriends
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');
        $hans->save();

        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals('Friend 1', \Relation1UserQuery::create()->filterByWho($hans)->findOne()->getName(), 'Hans\'s first friend is Friend 1.');

        \Map\Relation1UserTableMap::clearInstancePool();
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $this->assertCount(2, $newHansObject->getFriends(), 'two friends');

        $friends = new ObjectCollection();
        $friends[] = $friend3 = (new \Relation1User())->setName('Friend 3');
        $friends[] = $friend4 = (new \Relation1User())->setName('Friend 4');
        $newHansObject->setFriends($friends);
        $newHansObject->save();

        $this->assertEquals(5, \Relation1UserQuery::create()->count(), 'We have five users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals('Friend 3', \Relation1UserQuery::create()->filterByWho($hans)->findOne()->getName(), 'Hans\'s first friend is Friend 3.');


        /*
         * ####################################
         * Special: Add friend to db and fire addFriend on a new instance.
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');

        //get new instance of $hans and fire addFriend
        \Map\Relation1UserTableMap::clearInstancePool();
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $friend2->save();
        $newHansObject->addFriend($friend2);
        $this->assertCount(2, $newHansObject->getFriends(), 'two friends');

        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has two friends.');


        /*
         * ####################################
         * Special: addFriend same friend as the one in the database.
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');

        //check if next addFriend
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');


        /*
         * ####################################
         * Special: addFriend, addFriend, removeFriend
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friends');

        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->removeFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friends');

        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 2 as friend');


        /*
         * ####################################
         * Special: addFriend, addFriend, removeFriend different order
         */
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->removeFriend($friend2);
        $this->assertCount(1, $hans->getFriends(), 'one friends');

        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');

    }

    public function testRelationThree()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation2_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="position_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation2_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation2_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation2_position" phpName="Position">
                <reference local="position_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation2_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation2_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation2_position">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>
    </database>
        ';

        $this->buildAndMigrate($schema);

        /**
         *
         *           add     |    remove     |    set     |    get
         *        +---------------------------------------------------
         * add    |    1             2             3            4
         * remove |    5             6             7            8
         * set    |    9            10            11           12
         *
         */

        /*
         * ####################################
         * 1. add, add
         */
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $positionLead = new \Relation2Position();
        $positionLead->setName('Lead');
        $hans->addGroup($admins, $positionLead);
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        /*
         * ####################################
         * 2. add, remove
         */
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $hans->removeGroupPosition($admins, $position);
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \Relation2UserGroupQuery::create()->count(), 'We have one connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

    }


    public function testRelationThree2()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation3_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>
            <column name="relation_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation3_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation3_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>

            <foreign-key foreignTable="relation3_relation" phpName="Relation">
                <reference local="relation_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation3_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation3_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

        <table name="relation3_relation">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>
    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation3UserGroupQuery::create()->deleteAll();
        \Relation3UserQuery::create()->deleteAll();
        \Relation3GroupQuery::create()->deleteAll();
        \Relation3RelationQuery::create()->deleteAll();

        $hans = new \Relation3User();
        $hans->setName('hans');

        $admins = new \Relation3Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $relation = new \Relation3Relation();
        $relation->setName('Leader');

        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');
        $hans->addGroup($admins, $relation);
        $this->assertCount(1, $hans->getGroupRelations(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation3UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation3UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation3UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, $relation);
        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation3UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation3UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation3UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');

    }

    public function testRelationThree3()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation4_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>
            <column name="relation" type="varchar" primaryKey="true"/>

            <foreign-key foreignTable="relation4_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation4_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>
        </table>

        <table name="relation4_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation4_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation4UserGroupQuery::create()->deleteAll();
        \Relation4UserQuery::create()->deleteAll();
        \Relation4GroupQuery::create()->deleteAll();

        $hans = new \Relation4User();
        $hans->setName('hans');

        $admins = new \Relation4Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');
        $hans->addGroup($admins, 'teamLeader');
        $this->assertCount(1, $hans->getGroupRelations(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation4UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation4UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation4UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, 'teamLeader');
        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation4UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation4UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation4UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');
    }

    public function testRelationThree4()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation5_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="relation" type="varchar" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation5_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>

            <foreign-key foreignTable="relation5_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation5_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation5_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation5UserGroupQuery::create()->deleteAll();
        \Relation5UserQuery::create()->deleteAll();
        \Relation5GroupQuery::create()->deleteAll();

        $hans = new \Relation5User();
        $hans->setName('hans');

        $admins = new \Relation5Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');
        $hans->addGroup($admins, 'teamLeader');
        $this->assertCount(1, $hans->getGroupRelations(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation5UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation5UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation5UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, 'teamLeader');
        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation5UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation5UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation5UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');
    }

    public function testRelationThree5()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation6_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation6_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>

            <foreign-key foreignTable="relation6_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation6_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation6_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation6UserGroupQuery::create()->deleteAll();
        \Relation6UserQuery::create()->deleteAll();
        \Relation6GroupQuery::create()->deleteAll();

        $hans = new \Relation6User();
        $hans->setName('hans');

        $admins = new \Relation6Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $this->assertCount(0, $hans->getGroups(), 'no groups');
        $hans->addGroup($admins);
        $this->assertCount(1, $hans->getGroups(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation6UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation6UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation6UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroup($admins);
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation6UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation6UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation6UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');
    }

}