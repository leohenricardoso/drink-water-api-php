## Endpoints

##### Criar novo usuário
POST ===> http://127.0.0.1/drink-water-api-php/users/
###### Example:
```json
{
	"email": "leohenricardoso756@gmail.com",
	"name": "leticia",
	"password": "senha123"
}
```

------------------------------------------------------------------

##### Login
POST ===> http://127.0.0.1/drink-water-api-php/login/
```json
{
	"email": "leohenricardoso666@gmail.com",
	"password": "senha123"
}
```

------------------------------------------------------------------

##### Buscar usuário por ID (necessário token)
GET ===> http://127.0.0.1/drink-water-api-php/users/
###### Example:
```json
{
	"iduser": 4
}
```
Ou

GET ===> http://127.0.0.1/drink-water-api-php/users?iduser=4

------------------------------------------------------------------

##### Listar todos os usuários (necessário token)
GET ===> http://127.0.0.1/drink-water-api-php/users/

------------------------------------------------------------------

##### Atualizar usuário por id (necessário token)
PUT ===> http://127.0.0.1/drink-water-api-php/users/
###### Example:
```json
{
	"iduser": 4,
	"name": "Novo Nome",
	"password": "novasenha"
}
```
Ou

PUT ===> http://127.0.0.1/drink-water-api-php/users?iduser=4
###### Example:
```json
{
	"name": "Novo Nome",
	"password": "novasenha"
}
```

------------------------------------------------------------------

##### Deleta usuário por id (necessário token do usuário a ser deletado)
DELETE ===> http://127.0.0.1/drink-water-api-php/users/
###### Example:
```json
{
	"iduser": 4
}
```
Ou

DELETE ===> http://127.0.0.1/drink-water-api-php/users?iduser=4

------------------------------------------------------------------

##### Beber agua (necessário token)
POST ===> http://127.0.0.1/drink-water-api-php/users/drink.php
###### Example:
```json
{
	"iduser": 4,
	"drink_ml": 200
}
```
