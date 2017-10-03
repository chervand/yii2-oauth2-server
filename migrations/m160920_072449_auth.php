<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m160920_072449_auth
 *
 * @see https://oauth2.thephpleague.com/access-token-repository-interface/
 * @see https://oauth2.thephpleague.com/client-repository-interface/
 * @see https://oauth2.thephpleague.com/refresh-token-repository-interface/
 * @see https://oauth2.thephpleague.com/scope-repository-interface/
 * @see https://oauth2.thephpleague.com/auth-code-repository-interface/
 * @see https://oauth2.thephpleague.com/user-repository-interface/
 */
class m160920_072449_auth extends Migration
{
    private $_tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    private static function _tables()
    {
        return [
            '{{%auth__client}}' => [
                'id' => Schema::TYPE_PK,
                'identifier' => Schema::TYPE_STRING . ' NOT NULL',
                'secret' => Schema::TYPE_STRING, // not confidential if null
                'name' => Schema::TYPE_STRING . ' NOT NULL',
                'redirect_uri' => Schema::TYPE_STRING,
                'token_type' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1', // Bearer
                'grant_type' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1', // Authorization Code
                'created_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'updated_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'status' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1', // Active,
                'KEY (token_type)',
                'KEY (grant_type)',
                'KEY (status)',
            ],
            '{{%auth__access_token}}' => [
                'id' => Schema::TYPE_PK,
                'client_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'user_id' => Schema::TYPE_INTEGER,
                'identifier' => Schema::TYPE_STRING . ' NOT NULL',
                'type' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1', // Bearer
                'mac_key' => Schema::TYPE_STRING,
                'mac_algorithm' => Schema::TYPE_SMALLINT,
                'created_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'updated_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'expired_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'status' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1', // Active,
                'FOREIGN KEY (client_id) REFERENCES {{%auth__client}} (id) ON DELETE CASCADE ON UPDATE CASCADE',
                'KEY (type)',
                'KEY (mac_algorithm)',
                'KEY (status)',
            ],
            '{{%auth__scope}}' => [
                'id' => Schema::TYPE_PK,
                'identifier' => Schema::TYPE_STRING . ' NOT NULL',
                'name' => Schema::TYPE_STRING,
            ],
            '{{%auth__client_scope}}' => [
                'client_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'scope_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'PRIMARY KEY (client_id, scope_id)',
                'FOREIGN KEY (client_id) REFERENCES {{%auth__client}} (id) ON DELETE CASCADE ON UPDATE CASCADE',
                'FOREIGN KEY (scope_id) REFERENCES {{%auth__scope}} (id) ON DELETE CASCADE ON UPDATE CASCADE',
            ],
            '{{%auth__access_token_scope}}' => [
                'access_token_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'scope_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'PRIMARY KEY (access_token_id, scope_id)',
                'FOREIGN KEY (access_token_id) REFERENCES {{%auth__access_token}} (id) ON DELETE CASCADE ON UPDATE CASCADE',
                'FOREIGN KEY (scope_id) REFERENCES {{%auth__scope}} (id) ON DELETE CASCADE ON UPDATE CASCADE',
            ],
            '{{%auth__refresh_token}}' => [
                'id' => Schema::TYPE_PK,
                'access_token_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'identifier' => Schema::TYPE_STRING . ' NOT NULL',
                'created_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'updated_at' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'status' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1', // Active,
                'FOREIGN KEY (access_token_id) REFERENCES {{%auth__access_token}} (id) ON DELETE CASCADE ON UPDATE CASCADE',
                'KEY (status)',
            ],
            '{{%auth__auth_code}}' => [
                'id' => Schema::TYPE_PK,
            ],
        ];
    }

    public function safeUp()
    {
        foreach (static::_tables() as $name => $attributes) {
            try {
                $this->createTable($name, $attributes, $this->_tableOptions);
            } catch (\Exception $e) {
                echo $e->getMessage(), "\n";
                return false;
            }
        }

        return true;
    }

    public function safeDown()
    {
        foreach (array_reverse(static::_tables()) as $name => $attributes) {
            try {
                $this->dropTable($name);
            } catch (\Exception $e) {
                echo "m160920_072449_oauth cannot be reverted.\n";
                echo $e->getMessage(), "\n";
                return false;
            }
        }

        return true;
    }
}
