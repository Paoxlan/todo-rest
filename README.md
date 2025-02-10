# todo-rest

A REST API Todo application, build using Symfony and PHP.

## Setup
git clone this repository.

Run composer install.

```bash
composer install
```

And start the server using the Symfony CLI.

```
symfony server:start
```

## Usage
You can access the api doc by going to /api/doc.

For testing the application you should be either using the api doc, Postman or Insomnia. You can access the following API endpoints

- /api/todo/{id} [GET]: Retrieves the specified Todo using an id.
- /api/todos [GET]: Retrieves a collection of Todos.
- /api/todo/create [POST]: Creates a new Todo.
- /api/todo/update/{id} [PUT/PATCH]: Updates specified Todo.
- /api/todo/delete/{id} [DELETE]: Deletes specified Todo.