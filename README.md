# RestApi Module
Module to create a rest API with ProcessWire.

> **Disclaimer:** This is an example, there is no guarantee this code is secure! Use at your own risk and/or send me PRs with improvements.

>**Credits:** go to [Benjamin Milde](https://github.com/LostKobrakai) for his code example on how to use FastRoute with ProcessWire and [Camilo Castro](https://gist.github.com/clsource) for this [Gist](https://gist.github.com/clsource/dc7be74afcbfc5fe752c)

### Install

Install the module and on the module page, make sure to save the page at least once to save the automatically created JWT Secret. 

The Rest-API should work now. To check you can use [Postman](https://www.getpostman.com/) or [Insomnia](https://insomnia.rest/) and run a GET Request: `http://yourhost.test/api/users`

However `http://yourhost.test/api/test` is not going to work, since this route needs Authentification (if you activated it in your settings).

All you routes are defined under /site/api/Routes.php. This folder will be created while you install the module (in case it's not, you can find the example content in the modules folder of this module under `apiTemplate`). To add new routes, just add items to the array in the following format:

```php
['httpMethod (e.g. GET', 'endpoint', HandlerClass::class, 'methodInHandlerClass'],
```

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
* | / | no Endpoint
OPTIONS, POST, DELETE | /auth | Logic for JWT Authorization

You can check the default routes in `DefaultRoutes.php` of the modules folder.

### JWT Auth

To use JWT-Auth you have to send a GET Request to http://yourhost/api/auth with two parameters, username and password. The API will create and return you the JWT-Token which you have to add as a header to every following request:

```
Authorization: Bearer+yourtoken
```

An example for a simple login form is implemented as a Vue SPA, you can find it in this repository: [include url]

### Helper

There is a small helper class, which exposes some often used functionality. At the moment there's basically just one function available, but I for my part use it all the time: `checkAndSanitizeRequiredParameters`. This function checks if the client send all the parameters required and sanitizes them against a specified ProcessWire sanitizer. To use it call it first thing in your Api endpoint function:
```php
public static function postWithSomeData($data) {
  // Check for required parameter "message" and sanitize with PW Sanitizer
  $data = ApiHelper::checkAndSanitizeRequiredParameters($data, ['message|text']);

  return "Your message is: " . $data->message;
}
```
