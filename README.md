# RestApi Module
Module to create a rest API with ProcessWire.

> **Disclaimer:** This is an example, there is no guarantee this code is secure! Use at your own risk and/or send me PRs with improvements.

>**Credits:** go to [Benjamin Milde](https://github.com/LostKobrakai) for his code example on how to use FastRoute with ProcessWire and [Camilo Castro](https://gist.github.com/clsource) for this [Gist](https://gist.github.com/clsource/dc7be74afcbfc5fe752c)

### Install

Install the module and on the module page, make sure to save the page at least once to save the automatically created JWT Secret. 

The Rest-API should work now. To check you can use [Postman](https://www.getpostman.com/) or [Insomnia](https://insomnia.rest/) and run a GET Request: `http://yourhost.test/api/users`

However `http://yourhost.test/api/test` is not going to work, since this route needs Authentification (if you activated it in your settings).

> It is generally a good idea, to use a secure HTTPS connection in production environments, especially if you transmit sensitive user data!

All you routes are defined under /site/api/Routes.php. This folder will be created while you install the module (in case it's not, you can find the example content in the modules folder of this module under `apiTemplate`). To add new routes, just add items to the array in the following format:

```php
['httpMethod (e.g. GET', 'endpoint', HandlerClass::class, 'methodInHandlerClass', ["options" => "are optional"],
```

With the optional options you can control the behaviour of the router, at the moment there is just one supported parameter:

| Parameter | Type | Default | Description
| --- | --- | --- | ---
| auth | Boolean | true | controls if Authorization is required for this route

> Check https://github.com/nikic/FastRoute#usage for more information about routing (e.g. url params like `/user/41`)

Also you need to require your handler classes you might create in Routes.php.

You can also create groups, which makes it a bit easier to create multiple sub-routes for the same endpoint (for example it is a good idea to version your API):

```php
'v1' => [
  ['GET', 'posts', Posts::class, 'getAllPosts'],
],
'v2' => [
  ['GET', 'posts', NewPostsClass::class, 'getAllPosts'],
],
```

This is going to create the following endpoints for your API:

```
/v1/posts
/v2/posts
```

There are some default routes defined in the module:

| Method | Route | Description
| --- | --- | ---
| * | / | no Endpoint
| OPTIONS, POST, DELETE | /auth | Logic for JWT Authorization

You can check the default routes in `DefaultRoutes.php` of the modules folder.

### Endpoint

Currently the endpoint for the api is hardcoded to `/api`. That means a page with the name `api` is not going to work if you`ve installed this module. I might make the endpoint configurable via module settings in the future.

### JWT Auth

To use JWT-Auth you have to send a GET Request to http://yourhost/api/auth with two parameters, username and password. The API will create and return you the JWT-Token which you have to add as a header to every following request:

```
Authorization: Bearer+yourtoken
```

An example for a simple login form is implemented as a Vue SPA, you can find it in this repository: https://github.com/thomasaull/RestApi-Vue-Example

### Helper

There is a small helper class, which exposes some often used functionality. At the moment there's basically just one function available, but I for my part use it all the time: `checkAndSanitizeRequiredParameters`. This function checks if the client send all the parameters required and sanitizes them against a specified ProcessWire sanitizer. To use it call it first thing in your Api endpoint function:
```php
public static function postWithSomeData($data) {
  // Check for required parameter "message" and sanitize with PW Sanitizer
  $data = RestApiHelper::checkAndSanitizeRequiredParameters($data, ['message|text']);

  return "Your message is: " . $data->message;
}
```
