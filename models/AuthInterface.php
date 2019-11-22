<?php

namespace app\models;

/**
 * AuthInterface
 * 
 * AuthInterface is the interface that should be implemented by classes who implement
 * an authentication method.
 */
interface AuthInterface
{

    /**
     * Authenticate a user.
     * 
     * @param string $username the username given from the login form
     * @param string $password the password given from the login form
     * @return bool authentication success or failure
     */
    public function authenticate ($username, $password);

    /**
     * A function to query for users to migrate.
     * This function returns nothing, and the [[migrateUsers]] array should be set in
     * the following format:
     * 
     * $this->migrateUsers = [
     *      'username1 -> identifier1' => 'username1',
     *      'username2 -> identifier2' => 'username2',
     *      ...
     *      'usernameN -> identifierN' => 'usernameN',
     * ];
     *
     * where 'username' is the username from the local database and 'identifier' is the user
     * identifier in from the authentication method. Identifier may also be "NULL", which
     * will set the identifier in the database to null (this is for a migration of a user
     * back to the local authentication method).
     *
     * @param string $attribute the attribute currently being validated
     * @param mixed $params the value of the "params" given in the rule
     * @param \yii\validators\InlineValidator $validator related InlineValidator instance.
     * This parameter is available since version 2.0.11.
     */
    public function queryUsers ($attribute, $params, $validator);

}

?>