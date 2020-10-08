<?php
header('Content-Type: application/json; charset=utf-8');
require_once('User.php');
require_once('../config/database.php');

class Api
{
    private $db;
    private $method;
    private $body;
    private $header;

    public function __construct(
        string $method = 'GET',
        array $body = [],
        array $header = []
    ) {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->method = $method;
        $this->body = $body;
        $this->header = $header;
    }

    /**
     * Checks whether the request is for a user or
     * for a user list and returns them
     *
     * @return void
     */
    public function getOrListUser()
    {
        try {
            if (isset($this->body['id_param_url'])) {
                $id = $this->body['id_param_url'];
            } elseif (isset($this->body['iduser'])) {
                $id = $this->body['iduser'];
            }

            if (isset($this->header['Authorization'])) {
                $token = $this->header['Authorization'];
            }

            if (empty($token)) {
                throw new Exception('Token is necessary', 403);
            }

            $users = new User($this->db);

            if (empty($users->getUserByToken($token))) {
                throw new Exception('Invalid token', 401);
            }

            if (!empty($id)) {
                $userData = $users->getById($id);
            } else {
                $userData = $users->getAll();
            }

            if ($userData) {
                http_response_code(200);
                echo json_encode($userData);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(
                array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Create a new user
     *
     * @return void
     */
    public function createUser()
    {
        try {
            if (isset($this->body['email'])) {
                $email = $this->body['email'];
            }

            if (isset($this->body['name'])) {
                $name = $this->body['name'];
            }

            if (isset($this->body['password'])) {
                $password = $this->body['password'];
            }

            $userData = (new User($this->db, $name, $email, $password))->create();

            if ($userData) {
                http_response_code(200);
                echo json_encode(
                    array("message" => "Success")
                );
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(
                array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Updates user data
     *
     * @return array
     */
    public function updateUser()
    {
        try {
            if (isset($this->body['id_param_url'])) {
                $id = $this->body['id_param_url'];
            } elseif (isset($this->body['iduser'])) {
                $id = $this->body['iduser'];
            } else {
                throw new Exception('Error: missing the "iduser" in the body or in the request url', 428);
            }

            if (empty($this->header['Authorization'])) {
                http_response_code(401);
                echo json_encode(
                    array("message" => 'Token invalid.')
                );
            }

            $token = $this->header['Authorization'];

            $user = new User($this->db);

            $user->verifyUsersToken($token, $id);

            $parameters = $user->setParameters($this->body);

            $user->update($id, $parameters);

            http_response_code(200);
            return $user->getById($id);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(
                array("message" => $e->getMessage())
            );
        }
    }

    /**
     * Delete logged user in by their ID and token
     *
     * @return void
     */
    public function deleteUser()
    {
        try {
            if (isset($this->body['id_param_url'])) {
                $id = $this->body['id_param_url'];
            } elseif (isset($this->body['iduser'])) {
                $id = $this->body['iduser'];
            } else {
                throw new Exception('Error: missing the "iduser" in the body or in the request url', 428);
            }

            if (empty($this->header['Authorization'])) {
                http_response_code(401);
                echo json_encode(
                    array("message" => 'Token invalid.')
                );
            }

            $token = $this->header['Authorization'];

            $user = new User($this->db);

            $user->verifyUsersToken($token, $id);

            $userDeleted = $user->getById($id);

            $user->delete($id);

            http_response_code(200);
            echo json_encode(
                array("message" => "Deleted - Success")
            );
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(
                array("message" => $e->getMessage())
            );
        }
    }

    /**
     * Increase the water meter drunk by the user
     *
     * @return void
     */
    public function drinkWater()
    {
        try {
            if (isset($this->body['id_param_url'])) {
                $id = $this->body['id_param_url'];
            } elseif (isset($this->body['iduser'])) {
                $id = $this->body['iduser'];
            } else {
                throw new Exception('Error: missing the "iduser" in the body or in the request url', 428);
            }

            if (empty($this->body['drink_ml'])) {
                throw new Exception('"drink_ml" is missing', 428);
            }

            if (empty($this->header['Authorization'])) {
                throw new Exception('"token" is missing', 428);
            }

            $drink_ml = $this->body['drink_ml'];
            $token = $this->header['Authorization'];

            $user = new User($this->db);

            $user->verifyUsersToken($token, $id);

            $user->drink($id, $drink_ml);

            $user = $user->getById($id);

            http_response_code(200);
            echo json_encode(
                $user
            );
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(
                array("error" => $e->getMessage())
            );
        }
    }

    /**
     * Add or change user token and return your data
     *
     * @return void
     */
    public function login()
    {
        try {
            if (empty($this->body['email'])) {
                throw new Exception('"email" is missing', 428);
            }

            if (empty($this->body['password'])) {
                throw new Exception('"password" is missing', 428);
            }

            $email = $this->body['email'];
            $password = $this->body['password'];

            $userData = (new User($this->db))->login($email, $password);

            if ($userData) {
                http_response_code(200);
                echo json_encode(
                    $userData
                );
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(
                array('error' => $e->getMessage())
            );
        }
    }
}
