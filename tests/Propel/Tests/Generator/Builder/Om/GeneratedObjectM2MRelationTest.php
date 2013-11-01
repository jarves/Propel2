<?php

namespace Propel\Tests\Issues;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
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

        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $peter = new \Relation1User();
        $peter->setName('peter');

        $this->assertCount(0, $hans->getFriends(), 'no friends');
        $hans->addFriend($peter);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->save();

        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users stored.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection stored.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend stored.');

        $hans->removeFriend($peter);
        $this->assertCount(0, $hans->getFriends(), 'no friends');

        $hans->save();

        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users stored.');
        $this->assertEquals(0, \Relation1UserFriendQuery::create()->count(), 'We have zero connection stored.');
        $this->assertEquals(0, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has zero friends stored.');
    }

    public function testRelationThree()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation2_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="relation_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation2_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation2_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation2_relation" phpName="Relation">
                <reference local="relation_id" foreign="id"/>
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

        <table name="relation2_relation">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>
    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2RelationQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $relation = new \Relation2Relation();
        $relation->setName('Leader');

        $this->assertCount(0, $hans->getGroups(), 'no groups');
        $hans->addGroup($admins, $relation);
        $this->assertCount(1, $hans->getGroups(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection stored.');
        $this->assertEquals(1, \Relation2UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, $relation);
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(0, \Relation2UserGroupQuery::create()->count(), 'We have zero connection stored.');
        $this->assertEquals(0, \Relation2UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups stored.');
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

        $this->assertCount(0, $hans->getGroups(), 'no groups');
        $hans->addGroup($admins, $relation);
        $this->assertCount(1, $hans->getGroups(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation3UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(1, \Relation3UserGroupQuery::create()->count(), 'We have one connection stored.');
        $this->assertEquals(1, \Relation3UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, $relation);
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation3UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(0, \Relation3UserGroupQuery::create()->count(), 'We have zero connection stored.');
        $this->assertEquals(0, \Relation3UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups stored.');

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

        $this->assertCount(0, $hans->getGroups(), 'no groups');
        $hans->addGroup($admins, 'teamLeader');
        $this->assertCount(1, $hans->getGroups(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation4UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(1, \Relation4UserGroupQuery::create()->count(), 'We have one connection stored.');
        $this->assertEquals(1, \Relation4UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, 'teamLeader');
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation4UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(0, \Relation4UserGroupQuery::create()->count(), 'We have zero connection stored.');
        $this->assertEquals(0, \Relation4UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups stored.');
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

        $this->assertCount(0, $hans->getGroups(), 'no groups');
        $hans->addGroup($admins, 'teamLeader');
        $this->assertCount(1, $hans->getGroups(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation5UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(1, \Relation5UserGroupQuery::create()->count(), 'We have one connection stored.');
        $this->assertEquals(1, \Relation5UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, 'teamLeader');
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation5UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(0, \Relation5UserGroupQuery::create()->count(), 'We have zero connection stored.');
        $this->assertEquals(0, \Relation5UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups stored.');
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

        $this->assertEquals(1, \Relation6UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(1, \Relation6UserGroupQuery::create()->count(), 'We have one connection stored.');
        $this->assertEquals(1, \Relation6UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroup($admins);
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation6UserQuery::create()->count(), 'We have one user stored.');
        $this->assertEquals(0, \Relation6UserGroupQuery::create()->count(), 'We have zero connection stored.');
        $this->assertEquals(0, \Relation6UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups stored.');
    }

}