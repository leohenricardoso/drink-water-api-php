<?php
require_once('../config/database.php');

class User
{
    private $conn;
    private $tableName = "users";

    private $id;
    private $name;
    private $email;
    private $password;
    private $drink_counter = 0;
    private $drink_ml = 0;
    private $token;

    public function __construct($db, $name = '', $email = '', $password = '')
    {
        $this->conn = $db;

        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Create new user
     *
     * @return object
     */
    public function create()
    {
        if ($this->emailExists($this->email)) {
            throw new \Exception('Email already taken.', 500);
        }

        $this->generateToken();

        $query = "INSERT INTO `users` (
            `email`,
            `name`,
            `password`,
            `drink_counter`,
            `token`
        ) VALUES (
            '{$this->email}',
            '{$this->name}',
            '{$this->password}',
            '{$this->drink_counter}',
            '{$this->token}'
        );";

        $data = $this->conn->prepare($query);
        $data->execute();
        return $data;
    }

    /**
     * Get user by Email
     *
     * @param string $email
     * @return object
     */
    public function getUserByEmail($email)
    {
        $this->emailFormat($email);

        $query = "SELECT
            `id`,
            `email`,
            `name`,
            `password`,
            `drink_counter`
        FROM
            `users`
        WHERE
            `email` = '{$email}';";

        $data = $this->conn->prepare($query);
        $data->execute();

        return $data;
    }

    /**
     * Get user by token
     *
     * @param string $token
     * @return object
     */
    public function getUserByToken($token)
    {

        $token = explode(' ', $token);
        $token = $token[1];

        $query = "SELECT
            `id`,
            `name`,
            `email`,
            `drink_counter`,
            `token`
        FROM
            `users`
        WHERE
            `token` = '{$token}';";

        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();
        if ($num > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get users list
     *
     * @return object
     */
    public function getAll()
    {
        $query = "SELECT * FROM " . $this->tableName . "";
        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();

        if ($num > 0) {

            $users = array();
            $users['itemCount'] = $num;
            $users['data'] = array();

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $items = array(
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'drink_counter' => $drink_counter
                );
                $users['data'][] = $items;
            }

            http_response_code(200);
            echo json_encode($users);
        } else {
            http_response_code(404);

            echo json_encode(
                array("message" => "No users found.")
            );
        }
    }

    /**
     * Get user by id
     *
     * @param int $id
     * @return void
     */
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->tableName . " where id = " . $id;
        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();

        if ($num > 0) {

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user = array(
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'drink_counter' => $drink_counter,
                    'token' => $token
                );
            }

            return $user;
        } else {
            http_response_code(404);

            echo json_encode(
                array("message" => "No users found.")
            );
        }
    }

    /**
     * Update user
     *
     * @param int $iduser
     * @param array $parameters
     * @return void
     */
    public function update($iduser, $parameters)
    {
        $needNewToken = false;
        $update = '';
        foreach ($parameters as $key => $value) {
            if ($key == 'password') {
                $this->password = $this->passwordToHash($value);
                $value = $this->password;
                $needNewToken = true;
            } else if ($key == 'email') {
                $needNewToken = true;
            }

            $update .= "`{$key}` = '{$value}', ";
        }

        if ($needNewToken) {
            $this->generateToken();
            $update .= "`token` = '{$this->token}', ";
        }

        $update = substr_replace($update, ' ', -2);

        $query = "UPDATE
            `users`
        SET
            {$update}
        WHERE
            `id` = {$iduser};";

        $data = $this->conn->prepare($query);
        $data->execute();

        http_response_code(200);
        echo json_encode(
            array("message" => "Success")
        );
    }

    /**
     * Delete user data by id
     *
     * @param int $id
     * @return void
     */
    public function delete($id)
    {
        $query = "DELETE
        FROM
            `users`
        WHERE
            `id` = {$id};";

        $data = $this->conn->prepare($query);
        return $data->execute();
    }

    /**
     * Add or change user token and return your data
     *
     * @param string $email
     * @param string $password
     * @return object
     */
    public function login($email, $password)
    {
        $this->emailFormat($email);

        if (!$this->emailExists($email)) {
            throw new Exception('User not found', 404);
        }

        $password = $this->passwordToHash($password);

        $query = "SELECT
            `id`,
            `name`,
            `email`,
            `drink_counter`,
            `token`
        FROM
            `users`
        WHERE
            `email` = '{$email}'
            AND `password` = '{$password}';";

        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();

        if ($num > 0) {

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user = array(
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'drink_counter' => $drink_counter,
                    'token' => $token
                );
            }

            if (isset($user['token'])) {
                $this->generateToken();

                $query2 = "UPDATE
                    `users`
                SET
                    `token` = '{$this->token}'
                WHERE
                    `id` = '{$user['id']}';";

                $dataWithToken = $this->conn->prepare($query2);
                $dataWithToken->execute();

                $user['token'] = $this->token;
            }

            return $user;
        } else {
            http_response_code(404);
            echo json_encode(
                array("message" => "No user found, please, verify entered data.")
            );
        }

        return $result;
    }

    /**
     * Checks and takes which parameters were passed in the request body
     *
     * @param array $body
     * @return array
     */
    public function setParameters($body)
    {
        $parameters = [];
        if (isset($body['name'])) {
            $parameters['name'] = $body['name'];
        }

        if (isset($body['email'])) {
            if ($this->emailExists($body['email'])) {
                throw new Exception('Email already taken.', 500);
            }

            $parameters['email'] = $body['email'];
        }

        if (isset($body['password'])) {
            $parameters['password'] = $body['password'];
        }

        return $parameters;
    }

    /**
     * Checks whether the passed token matches the user's token
     *
     * @param string $token
     * @param int $iduser
     * @return bool
     */
    public function verifyUsersToken($token, $iduser)
    {
        $user = $this->getById($iduser);

        if (!$this->getUserByToken($token)) {
            throw new Exception('Invalid token aaaa', 401);
        }

        $user['token'] = 'Bearer ' . $user['token'];

        if ($token != $user['token']) {
            throw new Exception('Token does not belong to user', 403);
        }


        return true;
    }

    /**
     * Add sha1 encrypt to password
     *
     * @param string $password
     * @return string
     */
    private function passwordToHash(string $password): string
    {
        return sha1($password);
    }

    /**
     * Increase the water meter drunk by the user
     *
     * @param int $id
     * @param int $drink_ml
     * @return bool
     */
    public function drink($id, $drink_ml)
    {
        $query = "UPDATE
            `users`
        SET
            `drink_counter` = `drink_counter` + 1,
            `drink_ml` = `drink_ml` + $drink_ml
        WHERE
            `id` = {$id};";

        $data = $this->conn->prepare($query);
        $data->execute();

        return true;
    }

    /**
     * Create sha1 encrypted token based on email and password
     *
     * @return void
     */
    private function generateToken()
    {
        $this->token = sha1("{$this->email}{$this->password}");
    }

    /**
     * Checks if email passed as parameter is already registered
     *
     * @param string $email
     * @return int
     */
    public function emailExists(string $email)
    {
        $data = $this->getUserByEmail($email);
        $num = $data->rowCount();

        return $num;
    }

    /**
     * Check the email format passed by parameter
     *
     * @param string $email
     * @return string
     */
    public function emailFormat($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format', 500);
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
