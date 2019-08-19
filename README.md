# RestApi Module

Module to create a rest API with ProcessWire.

> **Disclaimer:** This is an example, there is no guarantee this code is secure! Use at your own risk and/or send me PRs with improvements.

>**Credits:** go to [Benjamin Milde](https://github.com/LostKobrakai) for his code example on how to use FastRoute with ProcessWire and [Camilo Castro](https://gist.github.com/clsource) for this [Gist](https://gist.github.com/clsource/dc7be74afcbfc5fe752c)

## Install

Install the module and on the module page, make sure to save the page at least once to save the automatically created JWT Secret. 

The Rest-API should work now. To check you can use [Postman](https://www.getpostman.com/) or [Insomnia](https://insomnia.rest/) and run a GET Request: `http://yourhost.test/api/users`

However `http://yourhost.test/api/test` is not going to work, since this route needs Authentification (if you activated session or jwt authentication method in your settings).

> It is generally a good idea, to use a secure HTTPS connection in production environments, especially if you transmit sensitive user data!

All you routes are defined under /site/api/Routes.php. This folder will be created while you install the module (in case it's not, you can find the example content in the modules folder of this module under `apiTemplate`). To add new routes, just add items to the array in the following format:

```php
['httpMethod (e.g. GET', 'endpoint', HandlerClass::class, 'methodInHandlerClass', ["options" => "are optional"],
```

With the optional options you can control the behaviour of the router, at the moment there is just one supported parameter:

| Parameter | Type | Default | Description
| --- | --- | --- | ---
| auth | Boolean | true | controls if Authorization is required for this route
| application | Integer |  | if an application id is set, other applications cannot access this route.
| applications | Array<Integer> | [] | If set, only these application-ids are allowed to access the route.
| roles | Array<Integer>, Array<Role>, WireArray<Role> | [] | If set, only these roles can access the route.

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
| OPTIONS, POST | /access | Logic for JWT access-token authorization (Only used for double-jwt auth)

You can check the default routes in `DefaultRoutes.php` of the modules folder.

## Endpoint

The default endpoint for the API is `/api`. That means a page with the name `api` is not going to work if you've installed this module. However, the endpoint is configurable in the module settings (falls back to `api` if no value is present).

## Applications

You configure applications which are allowed to use your api-endpoints. When the module is successfully installed you will find a new section "RestApi" under the setup entry in ProcessWire's main menu. Add a new application, save it and create your first api-key to continue. 

Each request to your endpoint must contain a valid api-key that is uniquely assigned to an application. The `X-API-KEY` header is designated to hold your apikey.

## Authorization

You can choose between `session`, `single-jwt` and `double-jwt` in the settings of each application. Each request, no matter which Auth-Type chosen, must contain an api-key that is linked with 

### Authorization: Session

If you are using axios you need to include the `withCredentials` options to make it work cross-origin:

```
axios.defaults.withCredentials = true
```

### Authorization: Single JWT

To get a jwt-token, you have to call your endpoint's /auth path via POST-request with a valid combination of username/password. I would recommend to send it via Basic Authorization-Header:

```
Authorization: Basic + {{username + ':' + password, base64 encoded}}
```

Alternatively you can send username and password as POST-params. The api will return you a refresh-token:

```json
{
  "jwt": "eaisodnhbGciOaskd1NiJ9.eyJpc3MiOiJtdXNpY2Fasondksb2NgdfZCI6NCwic3ViIjo0MCwiaWF0IjoxNTY2MTUxMjQ5LCJuYmYiOj904xNTEthisisonlyanexamplemp0aSI234VSmNoin123IasfnoAszNzY0OSwic2lkIjoicHFlZ3ExbGYzMHB0Nasol342fY2hhbGxlbmdlIjoiWnBPSVFsoV2RRS0p3eVlCdDhoisalkdeowifasddnfQ.VEY68-zAjx3QYWx3fodoYfFcc4242aYNPMRFS4Ws",
  "username": "sebi"
}
```

An example for a simple login form is implemented as a Vue SPA, you can find it in this repository: https://github.com/thomasaull/RestApi-Vue-Example

### Double JWT

Double JWT Auth is a great way to authenticate your users for external apps. In addition to single-token-auth it generates a second, longer-living token, that can be used to renew the short-living access-token. If a user is regulary active, they will not have to log in via username/password again.

To get a first refresh-token, you have to call your endpoint's /auth path via POST-request with a valid combination of username/password. I would recommend to send it via Basic Authorization-Header:

```
Authorization: Basic + {{username + ':' + password, base64 encoded}}
```

Alternatively you can send username and password as POST-params. The api will return you a refresh-token:

```json
{
  "refresh_token": "eaisodnhbGciOaskd1NiJ9.eyJpc3MiOiJtdXNpY2Fasondksb2NgdfZCI6NCwic3ViIjo0MCwiaWF0IjoxNTY2MTUxMjQ5LCJuYmYiOj904xNTEthisisonlyanexamplemp0aSI234VSmNoin123IasfnoAszNzY0OSwic2lkIjoicHFlZ3ExbGYzMHB0Nasol342fY2hhbGxlbmdlIjoiWnBPSVFmQ3doV2RRS0p3eVlCdDhoisalkdeowifasddnfQ.VEY68-zAjx3QYWx3fodoYfFcc4242aYNPMRFS4Ws",
  "username": "sebi"
}
```

With this token you can call the /access endpoint to get an access-token. Set the refresh-token to your `Authorization`-Header to make this call:

```
Authorization: Bearer+refreshtoken
```

_Result:_

```
{
  "access_token": "eyJ0eXAithisisonlyanexampleJhbaspfNiJ9.eyJpc3MiaXNpY2Fs39024hbCIsoin93ywic3ViIjo0MSw23inrdsfwsdTY223MDM0LCJuLINSjE1NjYy__mp0aSI6Ikp5T4242426OINSvUGwesindU2NjMyMzQzNCwic2lkIjoiM3FiZWVjM203sTNqZjFzamg3NTRs09joiaois9E2R3lsNzVydnJ0VXlWY2p5NnZ6RjZSZ3dUZUUiLCJydGtsebiFE3MsRkbHdiodf8n0.8FX7YFY6AEtsDA0fk39tvjgasdoind2rmMd4k2ck",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJ___IUsdioniJ9.eyJpc3Mi94ZhYnJpay5sb--sImF1ZCI6Mywic3ViIjo0MSwiaWF0IjoxNTY0NTU5OTthisisonlyanexampleasd1NTk5MzksImp0aSI6IjFYcVNJMURasodnpIsImV4cCI6MTU2ODs30.y7jdZ835Ne___mRf-BajiasBxwjNu42MTck4"
}
```

The api will return you an access-token, which can be used to authenticate in other requests (set it as Authorization-header like shown above). With the result comes a new refresh-token, too. A refresh-token can only be used once to get an access-token, so in your next call the new refresh-token should be used.

All active refresh-tokens are saved and validated with database-entries, which can be accessed in your application's settings. You can force a user to re-login by deleting their refresh-token. This will invalidate the user's session.

If your access-token expires, the api will answer any request with an 401-exception ('Access Token expired.'). In the resulting json you will get the value 'can_renew: true' which indicates that you should use your refresh-token to get a newer access-token. Your application could handle those exceptions and renew tokens quietly in the background.

## Exceptions

RestApi will answer common exceptions with corresponding http-statuscodes and a json-response with additional information to the exception. Look into the modules /classes/Exceptions.php for the used exception-classes. You can throw exceptions in your route-handlers as well. The module will catch and echo them as json with the statuscode of the exception.

## Helper

There is a small helper class, which exposes some often used functionality. At the moment there's basically just one function available, but I for my part use it all the time: `checkAndSanitizeRequiredParameters`. This function checks if the client send all the parameters required and sanitizes them against a specified ProcessWire sanitizer. To use it call it first thing in your Api endpoint function:
```php
public static function postWithSomeData($data) {
  // Check for required parameter "message" and sanitize with PW Sanitizer
  $data = RestApiHelper::checkAndSanitizeRequiredParameters($data, ['message|text']);

  return "Your message is: " . $data->message;
}
```
