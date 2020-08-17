# AppApi Module

**Connect your apps to ProcessWire!**

This module helps you to create an api, to which an app or an external service can connect to.

**A special thanks goes to [Thomas Aull](https://github.com/thomasaull)** , whose module [RestApi](https://modules.processwire.com/modules/rest-api/) was the starting point to this project. This module is not meant to replace this module because it does a great job. But if you want to connect and manage multiple apps or need other authentication methods, this module might help you.

**Credits:** go to [Benjamin Milde](https://github.com/LostKobrakai) for his code example on how to use FastRoute with ProcessWire and [Camilo Castro](https://gist.github.com/clsource) for this [Gist](https://gist.github.com/clsource/dc7be74afcbfc5fe752c)

| ProcessWire-Module: | [https://modules.processwire.com/modules/app-api/](https://modules.processwire.com/modules/app-api/)                                                                    |
| ------------------: | -------------------------------------------------------------------------- |
|      Support-Forum: | [https://processwire.com/talk/topic/24014-new-module-appapi/](https://processwire.com/talk/topic/24014-new-module-appapi/)                                                                      |
|         Repository: | [https://github.com/Sebiworld/AppApi](https://github.com/Sebiworld/AppApi) |

<a name="features"></a>

## Features

- **Simple routing definition**
- **Authentication** - Three different authentication-mechanisms are ready to use.
- **Access-management via UI**
- **Multiple different applications** with unique access-rights and authentication-mechanisms can be defined

## Table Of Contents

- [Features](https://github.com/Sebiworld/AppApi#features)
- [Installation](https://github.com/Sebiworld/AppApi#installation)
- [Defining Applications](https://github.com/Sebiworld/AppApi#defining-applications)
  - [Api-Keys](https://github.com/Sebiworld/AppApi#api-keys)
  - [PHP-Session (Recommended for on-site usage)](https://github.com/Sebiworld/AppApi#php-session)
  - [Single JWT (Recommended for external server-calls)](https://github.com/Sebiworld/AppApi#single-jwt)
  - [Double JWT (Recommended for apps)](https://github.com/Sebiworld/AppApi#double-jwt)
- [Creating Endpoints](https://github.com/Sebiworld/AppApi#creating-endpoints)
  - [Output Formatting](https://github.com/Sebiworld/AppApi#output-formatting)
  - [Error Handling](https://github.com/Sebiworld/AppApi#error-handling)
  - [Example: Listing Users](https://github.com/Sebiworld/AppApi#example-listing-users)
  - [Example: Universal Twack Api](https://github.com/Sebiworld/AppApi#example2-universal-twack-api)
    - [Routes](https://github.com/Sebiworld/AppApi#example2-routes)
    - [Page Handlers](https://github.com/Sebiworld/AppApi#example2-page-handlers)
    - [File Handlers](https://github.com/Sebiworld/AppApi#example2-file-handlers)
- [Versioning](https://github.com/Sebiworld/AppApi#versioning)
- [License](https://github.com/Sebiworld/AppApi#license)

<a name="installation"></a>

## Installation

AppApi can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

The prerequisites are **PHP>=7.2.0** and a **ProcessWire version >=3.93.0**. However, this is also checked during the installation of the module. No further dependencies.

<a name="defining-applications"></a>

## Defining Applications

![different-apps](https://raw.githubusercontent.com/Sebiworld/AppApi/master/documentation/media/different-apps.png)

You are free to define one, two, three or... Actually, there is no limit. You theoretically have no limit in the amount of different applications that can access data of the api. Each application has its own api-keys that allow a request to show to which app it belongs.

After installing the module you will find "AppApi" as a new item under the "Setup" popup-menu in the header bar. Click on "Manage applications" and choose "Add", to create a new application.

My module provides three different ways to authenticate to the api:

- For scripts that run in your website's frontend, I would recommend to use ProcessWire's default [PHP session authentication](https://github.com/Sebiworld/AppApi#php-session). If you are logged in, for example at your site's backend, you are logged in at your api's endpoints as well.
- If you want to access your api from an external server, to which you have full control to, you can use the [single JWT authentication](https://github.com/Sebiworld/AppApi#single-jwt). It is important to consider, that anyone, that knows an authentication-key, can legitimately authenticate to your endpoints. I would recommend to use this method only, if you can store the key securely and nobody but you can see it.
- The best way to connect any kind of app to your endpoint is to enable [double JWT authentication](https://github.com/Sebiworld/AppApi#double-jwt). Double JWT means, that an authenticated user gets a longer-living refresh-token and an access-token with only a short life. The access-token is used to legitimate any request. The request-token lets you get a new access-token, if the old one is expired. So, if anyone manages to intercept one of your requests and snatches a token, he can wreak havoc only temporarily until it expires.

So, choose wisely!

<img src="https://raw.githubusercontent.com/Sebiworld/AppApi/master/documentation/media/choose.gif" alt="Choose wisely" style="max-width:300px;" />

<a name="api-keys"></a>

### Api-Keys

Whichever of the three ways you choose, every application needs its own api-keys. Each request to your api has an api-key, that links it to its application.

Click on "Add new Apikey" on the bottom of the application form. Notice, that you must save a newly generated application once before that is possible.

![apikey](https://raw.githubusercontent.com/Sebiworld/AppApi/master/documentation/media/apikey.png)

A new apikey has a prefilled randomly generated key per default, but you can set it to any value you want. I recommend, to generate a new apikey per version of your app. That makes you able to revoke older apikeys. You can easily name your apikey like your version to make it clearer to understand. Additionally you can give your apikey a predefined expiry date.

To use an apikey for a request, you have to set it to the `X-API-KEY`-header.

This is, how you can do it with the Angular HttpClient:

```typescript
this.httpClient.get('https://my-website.dev/api/auth', {
  'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
});
```

> If you don't like that your api is available under /api/, you can change that in the module's settings to a custom path if you want to.

_By the way:_ If something goes wrong, you will get an informative error-message. If you are a superuser or your site runs in debug-mode, you will get even more information like this:

```jsonc
{
  "error": "Apikey not valid.",
  "devmessage": {
    "class": "ProcessWire\\AppApiException",
    "code": 401,
    "message": "Apikey not valid.",
    "location": "/Users/sebi/Developer/Test/my-website.local/site/modules/AppApi/classes/Router.php",
    "line": 130
  }
}
```

Generally speaking, you can count on consistent errors and status-codes from the module, which makes interacting with the api much easier. Read more about that in the section [Error Handling](#error-handling).

<a name="php-session"></a>

### PHP-Session (Recommended for on-site usage)

For this authentication method is not much explanation needed. Give your newly created application a speaking title and add a description. For a request, all you have to do is add the apikey as `X-API-KEY` header. If you are logged in, for example at your site's backend, you are logged in at your api's endpoints as well.

<a name="single-jwt"></a>

### Single JWT (Recommended for external server-calls)

If you want to access your api from an external server, to which you have full control to, you can use the single JWT authentication. It is important to consider, that anyone, that knows an authentication-key, can legitimately authenticate to your endpoints. I would recommend to use this method only, if you can store the key securely and nobody but you can see it.

In the configuration-view of your application you have to choose a "Token Secret". This secret is used to sign the JWT-tokens. It must not get leaked to anywhere else because everyone with this key could generate their own tokens - that means universal access to all endpoints of this application.

A random token secret with a reasonable length of 52 characters will be prefilled in a newly created application. But of course you have the possibility to change it to a custom value.

Let us now turn to the request. I will demonstrate it with simple Angular-code examples, but you surely can do the same thing in any other programming language as well. To get a jwt-token, that legitimates your further requests, you simply have to login with a valid username/password combination:

```typescript
// Example with username/pass as a basic-authorisation header (recommended):
this.httpClient.post('https://my-website.dev/api/auth', undefined, {
  'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  'authorization': 'Basic ' + btoa(username + ':' + pass),
});

// Alternatively you can send username/pass in the request-body:
this.httpClient.post(
  'https://my-website.dev/api/auth',
  JSON.stringify({
    username: username,
    password: pass,
  }),
  {
    'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  }
);
```

The resulting JSON response contains your token and the name of the authenticated user:

```jsonc
{
  "jwt": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjo0LCJzdWIiOjQxLCJpYXQiOjE1OTQ3NDk2OTUsIm5iZiI6MTU5NDc0OTY5NSwianRpIjoiSjdjTldsM3J0Q3VRSUN3azA2YW9LIiwiZXhwIjoxNTk3MzQxNjk1LCJzaWQiOiIxNDJhZmVhZG9jOTIybzJzZWJpaDciLCJzaWRfY2hhbGxlbmdlIjoiYmwwMzQyYXNpS0JIVS81a0g0Q0xWWGNzYWlNMTExOCJ9.9y4h0nslg2JHLSPFevOK-JWx2P_RfaaqSHRPi7nnSMk",
  "username": "sebi"
}
```

If you are interested in the data which is included in the token, you can easily decode it (for example online under [https://jwt.io](https://jwt.io)). The data is signed, so it cannot be changed without knowing the server's secret. A token that is generated from my AppApi-module contains the following data:

```jsonc
{
  "iss": "my-website.local", // issuer-claim: your domain
  "aud": 4, // audition-claim: the internal ID of your application
  "sub": 41, // subject-claim: userID of the authenticated user
  "iat": 1594749695, // issued-at-claim: creation-time
  "nbf": 1594749695, // not-before-claim: creation-time
  "jti": "J7cNWl3rtCuQICwk06aoK", // JWT-ID: unique id
  "exp": 1597341695, // expires-claim: token-expiration-timestamp
  "sid": "142afeadoc922o2sebih7", // Session-ID of the linked processwire-session
  "sid_challenge": "bl0342asiKBlU/5kH4CLVXcsaiM1118" // sid-challenge of the linked processwire-session
}
```

Every claim of an incoming token will be validated. If anything doesn't match, the token will be revoked.

With this functioning token you can now make authenticated requests! You only have to add it as a Bearer-authentication header:

```typescript
this.httpClient.get('https://my-website.dev/api/auth', {
  'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  'authorization': 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjo0LCJzdWIiOjQxLCJpYXQiOjE1OTQ3NDk2OTUsIm5iZiI6MTU5NDc0OTY5NSwianRpIjoiSjdjTldsM3J0Q3VRSUN3azA2YW9LIiwiZXhwIjoxNTk3MzQxNjk1LCJzaWQiOiIxNDJhZmVhZG9jOTIybzJzZWJpaDciLCJzaWRfY2hhbGxlbmdlIjoiYmwwMzQyYXNpS0JIVS81a0g0Q0xWWGNzYWlNMTExOCJ9.9y4h0nslg2JHLSPFevOK-JWx2P_RfaaqSHRPi7nnSMk',
});
```

This request proves, that I am now authenticated:

```jsonc
{
  "id": 41,
  "name": "sebi",
  "loggedIn": true
}
```

Without the authorization-header, I would only be a not-authenticated guest:

```jsonc
{
  "id": 40,
  "name": "guest",
  "loggedIn": false
}
```

**Wow! It works!**

<a name="double-jwt"></a>

### Double JWT (Recommended for apps)

The best way to connect any kind of app to your endpoint is to enable [double JWT authentication](https://github.com/Sebiworld/AppApi#double-jwt). Double JWT means, that an authenticated user gets a longer-living refresh-token and an access-token with only a short life. The access-token is used to legitimate any request. The refresh-token lets you get a new access-token, if the old one is expired. So, if anyone manages to intercept one of your requests and snatches a token, he can wreak havoc only temporarily until it expires.

An application with double-JWT authentication needs two different secrets that are configurable in the configuration-screen. The "Token Secret" is used to sign the refresh-tokens. The value of "Access-token Secret" is used, as you may already suspect, to sign all access-tokens. A random token secret with a reasonable length of 52 characters will be prefilled in a newly created application. But of course you have the possibility to change it to a custom value.

Let us now turn to the request. I will demonstrate it with simple Angular-code examples, but you surely can do the same thing in any other programming language as well. To get your first refresh-token you simply have to login with a valid username/password combination:

```typescript
// Example with username/pass as a basic-authorisation header (recommended):
this.httpClient.post('https://my-website.dev/api/auth', undefined, {
  'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  'authorization': 'Basic ' + btoa(username + ':' + pass),
});

// Alternatively you can send username/pass in the request-body:
this.httpClient.post(
  'https://my-website.dev/api/auth',
  JSON.stringify({
    username: username,
    password: pass,
  }),
  {
    'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  }
);
```

The resulting JSON response contains your refresh-token and the name of the authenticated user:

```jsonc
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjoyLCJzdWIiOjQxLCJpYXQiOjE1OTQ4Mzc0NDIsIm5iZiI6MTU5NDgzNzQ0MiwianRpIjoiaHd3NDlRSUN3azA2YTRrMXdGYmdWSiIsImV4cCI6MTU5NzQyOTQ0Mn0.fgWGmwzabHcecAzrPDxYf66Ie1Z0Vxl-H3oxMj0Asxc",
  "username": "sebi"
}
```

If you are interested in the data which is included in the token, you can easily decode it (for example online under [https://jwt.io](https://jwt.io)). The data is signed, so it cannot be changed without knowing the server's secret. A token that is generated from my AppApi-module contains the following data:

```jsonc
{
  "iss": "my-website.local", // issuer-claim: your domain
  "aud": 4, // audition-claim: the internal ID of your application
  "sub": 41, // subject-claim: userID of the authenticated user
  "iat": 1594749695, // issued-at-claim: creation-time
  "nbf": 1594749695, // not-before-claim: creation-time
  "jti": "J7cNWl3rtCuQICwk06aoK", // JWT-ID: unique id
  "exp": 1597341695 // expires-claim: token-expiration-timestamp
}
```

Every claim of an incoming token will be validated. If anything doesn't match, the token will be revoked.

The next step is to get an access-token, which you need to legitimize your api-requests. A refresh-token is only useful to apply for an access-token, nothing more. The target of your next api-request is the /auth/access-endpoint. Attach your refresh-token as a bearer token like I do in the following Angular-request:

```typescript
this.httpClient.get('https://my-website.dev/api/auth/access', {
  'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  authorization: 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjo0LCJzdWIiOjQxLCJpYXQiOjE1OTQ3NDk2OTUsIm5iZiI6MTU5NDc0OTY5NSwianRpIjoiSjdjTldsM3J0Q3VRSUN3azA2YW9LIiwiZXhwIjoxNTk3MzQxNjk1LCJzaWQiOiIxNDJhZmVhZG9jOTIybzJzZWJpaDciLCJzaWRfY2hhbGxlbmdlIjoiYmwwMzQyYXNpS0JIVS81a0g0Q0xWWGNzYWlNMTExOCJ9.9y4h0nslg2JHLSPFevOK-JWx2P_RfaaqSHRPi7nnSMk',
});
```

With the response you will get both - an access-token and a new refresh-token:

```jsonc
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjoyLCJzdWIiOjQxLCJpYXQiOjE1OTQ4Mzc1OTYsIm5iZiI6MTU5NDgzNzU5NiwianRpIjoiV25jNHlxSkF0dWJhb3NiMWhEMGYiLCJleHAiOjE1OTQ5MjM5OTYsInNpZCI6ImowNm0ydnZzZWJpZDAxNmpwZWdtZm9ld2kiLCJzaWRfY2hhbGxlbmdlIjoiQUJMcy5ibzEuVGppLklJRGxvNDJETFVaQy5aIiwicnRrbiI6Iko3Y05XbDNydEN1UUlDd2swNmFvSyJ9.x7_Yvq_WekIgWFZEpa4LEdTyhPEajhhYQF58bvG-ZFQ",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjoyLCJzdWIiOjQxLCJpYXQiOjE1OTQ4Mzc0NDIsIm5iZiI6MTU5NDgzNzQ0MiwianRpIjoiSjdjTldsM3J0Q3VRSUN3azA2YW9LIiwiZXhwIjoxNTk3NDI5NTk2fQ.yAuESeRaB5f-RRlkPisLVTyrCDWr_h4MeqmyOqflVGQ"
}
```

A refresh-token expires right after its usage. Because of that, when your access-token gets invalid, you have to use this new refresh-token to ask for a new one.

With your access-token you can now make authenticated requests! You only have to add it as a Bearer-authentication header:

```typescript
this.httpClient.get('https://my-website.dev/api/auth', {
  'x-api-key': 'ytaaYCMkUmouZawYMvJN9',
  authorization:
    'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJteS13ZWJzaXRlLmxvY2FsIiwiYXVkIjo0LCJzdWIiOjQxLCJpYXQiOjE1OTQ3NDk2OTUsIm5iZiI6MTU5NDc0OTY5NSwianRpIjoiSjdjTldsM3J0Q3VRSUN3azA2YW9LIiwiZXhwIjoxNTk3MzQxNjk1LCJzaWQiOiIxNDJhZmVhZG9jOTIybzJzZWJpaDciLCJzaWRfY2hhbGxlbmdlIjoiYmwwMzQyYXNpS0JIVS81a0g0Q0xWWGNzYWlNMTExOCJ9.9y4h0nslg2JHLSPFevOK-JWx2P_RfaaqSHRPi7nnSMk',
});
```

This request proves, that I am now authenticated:

```jsonc
{
  "id": 41,
  "name": "sebi",
  "loggedIn": true
}
```

Without the authorization-header, I would only be a not-authenticated guest:

```jsonc
{
  "id": 40,
  "name": "guest",
  "loggedIn": false
}
```

**Yay! It works!**

**But wait!** There is one more thing, that I want to mention relating to double JWT authentication. Mostly JWTs are used for authentication if we want to make it possible without storing any information about the user or the session on the server. As you have seen, our token contains all necessary information inside its data-claims. And it is signed with a secret key, which only or server knows. So nobody can manipulate any information of the token.

Although this works really great, I still wanted to have a little more control over the tokens that are given. So nevertheless, a reference to every double-JWT-token is stored in the module's database. You find a list of all active token-sessions in the configuration-view of your application.

![jwt-tokens](https://raw.githubusercontent.com/Sebiworld/AppApi/master/documentation/media/double-jwt-tokens.png)

If a user logs out, the session will automatically disappear from the list. You, as the admin, are able to force logout a user as well. Simply delete it from the list. With this action, the user is forced to login and create a new session. All tokens, that were created in relation to the old token are immediately invalid.

<a name="creating-endpoints"></a>

## Creating Endpoints

Creating individual endpoints is not very complicated. If you have used Thomas's [RestApi](https://modules.processwire.com/modules/rest-api/) module before, the fundamental concepts will look very similar to you. All your routes are defined under `/site/api/Routes.php`. This folder will be created while you install the module (in case it's not, you can find the example content in the modules folder of this module under `apiTemplate`). To add new routes, just add items to the array in the following format:

```php
// [httpMethod, endpoint, HandlerClass::class, methodInHandlerClass, ["options" => "are optional"],

// A GET-request to /api/test/ returns the output of Example::test():
['GET', 'test', Example::class, 'test']

// A POST-request to /api/test2/ returns the output of Example::test2()
['POST', 'test2', Example::class, 'postTest']

// This setting allows OPTION-requests to /api/test/:
['OPTIONS', 'test', ['GET', 'POST']],
```

Instead of my Example-class, you can call any other class as well. You only need to consider that the called function has to be static.

> If you don't like that your `Routes.php` is located under `/site/api/Router.php`, you can change that in the module's settings to a custom path if you want to.

You can also create groups, which makes it a bit easier to create multiple sub-routes for the same endpoint (for example it is a good idea to version your API):

```php
[
  'v1' => [
    ['GET', 'posts', Posts::class, 'getAllPosts'],
  ],
  'v2' => [
    ['GET', 'posts', NewPostsClass::class, 'getAllPosts'],
  ]
]
```

This is going to create the following endpoints for your API:

```
/v1/posts
/v2/posts
```

An optional fourth parameter can be set to add some automatic checks to a route:

| Parameter             | Type    | Example             | Description                                                                                         |
| --------------------- | ------- | ------------------- | --------------------------------------------------------------------------------------------------- |
| auth                  | boolean | true                | When true, authentication is required. Throws exception if not logged in.                           |
| roles                 | array   | ['admin', 'editor'] | If set, one of the roles in the array is required to use the route.                                 |
| application           | int     | 42                  | If set, the route is only allowed if the requesting apikey belongs to the application with this id. |
| applications          | array   | [3, 5, 42]          | Only the application-ids in the array are allowed to use the route                                  |
| handle_authentication | boolean  | false               | If set to false, all authentication-checks (apikey, tokens, ...) are disabled.                      |

You are free to combine the parameters in an array:

```php
['POST', 'test2', Example::class, 'postTest', [
  'auth' => true,										// Only a logged-in user can access the route
  'roles' => ['admin', 'editor'],		// Only admins and editors can access
  'applications' => [3, 5, 42]			// Only the applications with id 3, 5 or 42 can access
]]
```

<a name="output-formatting"></a>

### Output Formatting

The module automatically runs json_encode on the output data, so your function can return a classic PHP-array and it will be transformed to a JSON-response.

```php
<?php
namespace ProcessWire;

class Example {
  public static function test () {
    return [
      'message' => 'test successful',
      'status' => 200
    ];
  }
}
```

Calling that `Example::test()` function will result in a response like this:

```jsonc
{
  "message": "test successful",
  "status": 200
}
```

That works very well with all basic datatypes (like string, integer, boolean, ...) which json_encode can handle. More complex data, let's say a ProcessWire-Page object, must be transformed simpler datatypes. I added a little helper-function `AppApi::getAjaxOf()`, that can transform objects of ProcessWire's `Page`, `PageArray`, `Template`, `PageImage`, `PageFile` and `PageFiles` to arrays with the basic data that they contain.

```php
<?php
namespace ProcessWire;

class Example {
  public static function pageOutput () {
    return AppApi::getAjaxOf(wire('pages')->get('/'));
  }
}
```

Calling that `Example::pageOutput()` function will result in an array of the basic information of your homepage:

```jsonc
{
  "id": 1,
  "name": "home",
  "title": "Homepage",
  "created": 1494796565,
  "modified": 1494796588,
  "url": "/",
  "httpUrl": "https://my-website.dev/",
  "template": {
    "id": 1,
    "name": "home",
    "label": "Home-Template"
  }
}
```

<a name="error-handling"></a>

### Error Handling

AppApi itself returns informative errors for all internal processes, so you should be able to respond to an occuring error the best way possible. Look at the [Exceptions.php](https://github.com/Sebiworld/AppApi/blob/master/classes/Exceptions.php) file for a wide range of Exception-classes that are thrown throughout the code.

The module catches all kinds of occurring exceptions and errors, and instead returns an error message as JSON with correct status code header. Its not limited to the module's code, in your endpoint functions you can throw whatever you want as well!

```php
throw new \Exception('Internal Server Error.', 500);
```

If you throw this standard PHP-exception, you will get the following output with a `500 Internal Server Error` HTTP status header.

```jsonc
{
  "error": "Internal Server Error.",
  "devmessage": {
    "class": "Exception",
    "code": 500,
    "message": "Internal Server Error.",
    "location": "/Users/sebi/Developer/Test/my-website.local/site/modules/AppApi/classes/Router.php",
    "line": 130
  }
}
```

The part "devmessage" is only visible for superusers. And you can see it in every request if you have `wire('config')->debug;`enabled.

And I have one last special thing for you that makes the error handling much more flexible. All the internal exceptions extend the following `AppApiException` class (which you of course can extend for your own exceptions as well).

```php
class AppApiException extends WireException {
  private $additionals = array();

  public function __construct(string $message, int $code = 500, array $additionals = array(), \Exception $previous = null) {
    $this->additionals = array_merge($this->additionals, $additionals);
    parent::__construct($message, $code, $previous);
  }

  public function __toString() {
    return "{$this->message}";
  }

  public function getAdditionals() {
    return $this->additionals;
  }
}
```

Whats important here is the `additionals` array, which can hold additional parameters that will appear in the JSON-output as well.

```php
throw new AppApiException('Internal Server Error.', 418, [
  'my_favorite_number' => 42,
  'test' => 'it works!'
]);
```

Throwing this `AppApiException` will result in a response like this:

```jsonc
{
  "error": "Internal Server Error.",
  "my_favorite_number": 42,
  "test": "it works!",
  "devmessage": {
    "class": "Exception",
    "code": 418,
    "message": "I'm a teapot",
    "location": "/Users/sebi/Developer/Test/my-website.local/site/modules/AppApi/classes/Router.php",
    "line": 130
  }
}
```

So use these powers wisely and write clean code!

<img src="https://raw.githubusercontent.com/Sebiworld/AppApi/master/documentation/media/clean-code.gif" alt="clean code" style="max-width:100px;" />

<a name="example-listing-users"></a>

### Example: Listing Users

Let us get to a concrete example of an endpoint that enables an authenticated user to get a list of all users.

At first we need to define the routes in the file `/site/api/Routes.php` (or a custom file if you changed it in the module's settings):

```php
<?php
namespace ProcessWire;

// These two lines are necessary to make shure that everyting is loaded correctly:
require_once wire('config')->paths->AppApi . "vendor/autoload.php";
require_once wire('config')->paths->AppApi . "classes/AppApiHelper.php";

// After that, you define your classes that are called by the routes:
require_once __DIR__ . "/Example.php";

// The $routes-array will be imported by the module:
$routes = [
  'users' => [
    ['OPTIONS', '', ['GET']], // this is needed for CORS Requests
    ['GET', '', Example::class, 'getAllUsers', ["auth" => true]],
    ['OPTIONS', '{id:\d+}', ['GET']], // this is needed for CORS Requests
    ['GET', '{id:\d+}', Example::class, 'getUser', ["auth" => true]]
  ],
];
```

We defined two GET-endpoints, that are only available for logged-in users. If not logged-in, the module will throw an exception:

```php
throw new AppApiException('User does not have authorization', 401);
```

As you see, we don't have to bother much about authentication. Our routes are protected against unauthorized access. But keep in mind: You can throw a custom Exception at any point in your functions as well.

So, our next step is to define the Example-class which functions are called from our router:

```php
<?php
namespace ProcessWire;

class Example {
  /**
   * Returns a list of all users that are available
   */
  public static function getAllUsers() {
    // In our $response-array we collect everythin that should be returned:
    $response = [
      'users' => []
    ];

    // Collect id and username of every user and put it to the users-array:
    foreach(wire('users') as $user) {
      array_push($response['users'], [
        "id" => $user->id,
        "name" => $user->name
      ]);
    }

    return $response;
  }

  /**
   * Return username and id of one individual user
   */
  public static function getUser($data) {
    // $data will contain all GET-params, that were included in the request.
    // For POST-requests it would contain the response-body vars.

    // Use this helper-function to check and validate a parameter in one line.
    // An exception will be thrown, if $data['id'] does not exist
    $data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);

    // We collect our response-data in an empty StdClass. json_encode can handle StdClasses
    // as well, so its no problem.
    $response = new \StdClass();
    $user = wire('users')->get($data->id);

    // If the user does not exist, we throw a 404 exception and escape.
    if(!$user->id) throw new \Exception('User not found', 404);

    $response->id = $user->id;
    $response->name = $user->name;

    return $response;
  }
}
```

The response of our `/api/users/`-call will be something like that:

```jsonc
{
  "users": [
    { "id": 41, "name": "admin" },
    { "id": 42, "name": "test-user" },
    { "id": 123, "name": "anotheruser" }
  ]
}
```

The second call to, let's say `/api/users/42` will output the following JSON:

```jsonc
{
  "id": 42,
  "name": "test-user"
}
```

<a name="example2-universal-twack-api"></a>

### Example: Universal Twack Api

My second example is very practical, since it is my current best-practice solution for creating a complex ProcessWire-page with external connected applications. It includes my module [Twack](https://modules.processwire.com/modules/twack/) which is a nice way to structure the ProcessWire template-code in handy components. Another nice feature is the built-in support for outputting and merging json instead of html, which is a great advantage in combination with the AppApi module.

| ProcessWire-Module: | [https://modules.processwire.com/modules/twack/](https://modules.processwire.com/modules/twack/)   |
| ------------------: | -------------------------------------------------------------------------------------------------- |
|      Support-Forum: | [https://processwire.com/talk/topic/23549-twack/](https://processwire.com/talk/topic/23549-twack/) |
|         Repository: | [https://github.com/Sebiworld/Twack](https://github.com/Sebiworld/Twack)                           |

Our goal is to create a universal api, that makes every page accessible via api. If a page's frontend is limited to specific processwire-roles, it would have the same access-limitations for the api.

<a name="example2-routes"></a>

#### Routes:

So, lets start with our routes-definition:

```php
<?php

namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

require_once __DIR__ . '/TwackAccess.class.php';

$routes = [
  'page' => [
    ['OPTIONS', '{id:\d+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
    ['OPTIONS', '{path:.+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
    ['OPTIONS', '', ['GET', 'POST', 'UPDATE', 'DELETE']],
    ['GET', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
    ['GET', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
    ['GET', '', TwackAccess::class, 'dashboardRequest'],
    ['POST', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
    ['POST', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
    ['POST', '', TwackAccess::class, 'dashboardRequest'],
    ['UPDATE', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
    ['UPDATE', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
    ['UPDATE', '', TwackAccess::class, 'dashboardRequest'],
    ['DELETE', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
    ['DELETE', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
    ['DELETE', '', TwackAccess::class, 'dashboardRequest'],
  ],
  'file' => [
    ['OPTIONS', '{id:\d+}', ['GET']],
    ['OPTIONS', '{path:.+}', ['GET']],
    ['OPTIONS', '', ['GET']],
    ['GET', '{id:\d+}', TwackAccess::class, 'pageIDFileRequest'],
    ['GET', '{path:.+}', TwackAccess::class, 'pagePathFileRequest'],
    ['GET', '', TwackAccess::class, 'dashboardFileRequest']
  ]
];

```

We define `OPTIONS`, `GET`, `POST`, `UPDATE` and `DELETE` endpoints for these three routes:

| Route         | Example                       | Handler                         | Description                                                |
| ------------- | ----------------------------- | ------------------------------- | ---------------------------------------------------------- |
| **{id:\d+}**  | /api/page/42                  | TwackAccess::pageIDRequest()    | Request for a concrete page-id. It must be a number (_d+_) |
| **{path:.+}** | /api/page/projects/my-project | TwackAccess::pagePathRequest()  | Request for a page-path, as seen from the web-root         |
| **''**        | /api/page/                    | TwackAccess::dashboardRequest() | Request for the home-page                                  |

With these routes, every frontend-page should be callable. Via JWT-authentication we can even access protected pages. The module handles everything for us.

Additionally I needed to access page-files, which are often protected via `$config->pagefileSecure` on my ProcessWire-instances. This makes it impossible to access the files directly by their original path (i.e. `/site/assets/files/...`), because we are not authenticated via PHP-session. To enable accessing protected page-files, we need to define endpoints for that as well.

The file-endpoints work just like the page-endpoints. If you need a file of the page `/projects/my-project`, you have to call `/api/file/projects/my-project`. The filename is needed, too - it should be present in the GET-Parameter filename. (like `/api/file/projects/my-project?filename=testimage.jpg`). Please make sure, that the AppApi-headers `X-API-KEY` and your optional token must be present, too.

<a name="example2-page-handlers"></a>

#### Page-Handlers:

You're probably curious now how the route calls are handled. Let's have a quick look:

```php
<?php

namespace ProcessWire;

class TwackAccess {

  /**
   * Request for a special page-id.
   */
  public static function pageIDRequest($data) {
    // Sanitize and check ID:
    $data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);

    // Find the page:
    $page = wire('pages')->get('id=' . $data->id);

    // Call general page-output-function:
    return self::pageRequest($page);
  }

  /**
   * Request for root-page
   */
  public static function dashboardRequest() {
    $page = wire('pages')->get('/');
    return self::pageRequest($page);
  }

  /**
   * Request for a page-path
   */
  public static function pagePathRequest($data) {
    // Check and sanitize page-path (wire('sanitizer')->pagePathName())
    $data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['path|pagePathName']);

    // Find the page:
    $page = wire('pages')->get('/' . $data->path);

    // Call general page-output-function:
    return self::pageRequest($page);
  }

  /**
   * General function for page-outputs:
   */
  protected static function pageRequest(Page $page) {
    // Exit if Twack is not installed
    if (!wire('modules')->isInstalled('Twack')) {
      throw new InternalServererrorException('Twack module not found.');
    }

    // This commands Twack to output a data-array instead of HTML:
    wire('twack')->enableAjaxResponse();

    // If the page has no template, is not accessable or is blocked (i.e. via hook),
    // we throw a ForbiddenException
    if (!$page->viewable()) {
      throw new ForbiddenException();
    }

    $ajaxOutput   = $page->render();

    // $ajaxOutput will contain JSON-code, so we have to decode it to prevent it is encoded twice:
    $results      = json_decode($ajaxOutput, true);

    // Now, $results is a clean PHP-array with the information generated by Twack-components:
    return $results;
  }

  //...
}
```

Do you want to know, how you can render Twack-components as JSON? I extended the documentation so it now covers ajax-rendering: [https://github.com/Sebiworld/Twack#ajax-output](https://github.com/Sebiworld/Twack#ajax-output)

<a name="example2-file-handlers"></a>

#### File Handlers:

The file-requests are handled by the following functions:

```php
<?php

namespace ProcessWire;

class TwackAccess {
  /**
   * Request for a special page-id.
   */
  public static function pageIDFileRequest($data) {
    $data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['id|int']);
    $page = wire('pages')->get('id=' . $data->id);
    return self::fileRequest($page);
  }

  /**
   * Request for root-page
   */
  public static function dashboardFileRequest($data) {
    $page = wire('pages')->get('/');
    return self::fileRequest($page);
  }

  /**
   * Request for root-page
   */
  public static function pagePathFileRequest($data) {
    $data = AppApiHelper::checkAndSanitizeRequiredParameters($data, ['path|pagePathName']);
    $page = wire('pages')->get('/' . $data->path);
    return self::fileRequest($page);
  }

  /**
   * General file handler
   */
  protected static function fileRequest(Page $page) {
    // Extract the filename from GET-param
    $filename = wire('input')->get('file', 'filename');
    if (!$filename || !is_string($filename)) {
      // Exit with Exception 400 if no filename was found:
      throw new BadRequestException('No valid filename.');
    }

    // Check, if a file with the requested filename exists for the request-page:
    $file = $page->filesManager->getFile($filename);
    if (!$file || empty($file)) {
      throw new NotFoundException('File not found: ' . $filename);
    }

    // There are special options for image-files:
    if ($file instanceof Pageimage) {
      // Modify image-size with these parameters:
      $width     = wire('input')->get('width', 'intUnsigned', 0);
      $height    = wire('input')->get('height', 'intUnsigned', 0);
      $maxWidth  = wire('input')->get('maxwidth', 'intUnsigned', 0);
      $maxHeight = wire('input')->get('maxheight', 'intUnsigned', 0);
      $cropX     = wire('input')->get('cropx', 'intUnsigned', 0);
      $cropY     = wire('input')->get('cropy', 'intUnsigned', 0);

      $options = array(
        'webpAdd' => true
      );

      // Cropping:
      if ($cropX > 0 && $cropY > 0 && $width > 0 && $height > 0) {
        $file = $file->crop($cropX, $cropY, $width, $height, $options);
      }else if ($width > 0 && $height > 0) {
        $file = $file->size($width, $height, $options);
      }else if ($width > 0) {
        $file = $file->width($width, $options);
      }else if ($height > 0) {
        $file = $file->height($height, $options);
      }

      // Max-dimensions:
      if ($maxWidth > 0 && $maxHeight > 0) {
        $file = $file->maxSize($maxWidth, $maxHeight, $options);
      }else if ($maxWidth > 0) {
        $file = $file->maxWidth($maxWidth, $options);
      }else if ($maxHeight > 0) {
        $file = $file->maxHeight($maxHeight, $options);
      }
    }

    // Get general information about the file:
    $filepath = $file->filename;
    $fileinfo = pathinfo($filepath);
    $filename = $fileinfo['basename'];

    // Should the file be streamed?
    $isStreamable = !!isset($_REQUEST['stream']);

    if (!is_file($filepath)) {
      throw new NotFoundException('File not found: ' . $filename);
    }

    // Start reading the file:
    $filesize = filesize($filepath);
    $openfile    = @fopen($filepath, "rb");

    // Exit with error 500 if cannot read:
    if (!$openfile) {
      throw new InternalServererrorException();
    }

    // Set headers for the file request:
    header('Date: ' . gmdate("D, d M Y H:i:s", time()) . " GMT");
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s", filemtime($filepath)) . " GMT");
    header('ETag: "' . md5_file($filepath) . '"');
    header('Accept-Encoding: gzip, deflate');

    // Is Base64 requested?
    if (wire('input')->get('format', 'name', '') === 'base64') {
      $data = file_get_contents($filepath);
      echo 'data:' . mime_content_type($filepath) . ';base64,' . base64_encode($data);
      // We have to exit() to prevent the module to try a json_encode. We want to output base64 data.
      exit();
    }

    header("Pragma: public");
    header("Expires: -1");
    // header("Cache-Control: public,max-age=14400,public");
    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
    // header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Content-type: ' . mime_content_type($filepath));
    header('Content-Transfer-Encoding: binary');

    // If the file should be streamable, we can pause and resume the download at any point:
    if ($isStreamable) {
      header("Content-Disposition: inline; filename=\"$filename\"");
    } else {
      header("Content-Disposition: attachment; filename=\"$filename\"");
    }

    $range = '';
    if (isset($_SERVER['HTTP_RANGE']) || isset($_SERVER['HTTP_CONTENT_RANGE'])) {
      if(isset($_SERVER['HTTP_CONTENT_RANGE'])){
        $rangeParts = explode(' ', $_SERVER['HTTP_CONTENT_RANGE'], 2);
      }else{
        $rangeParts = explode('=', $_SERVER['HTTP_RANGE'], 2);
      }

      $sizeUnit = false;
      if (isset($rangeParts[0])) {
        $sizeUnit = $rangeParts[0];
      }

      $rangeOrig = false;
      if (isset($rangeParts[1])) {
        $rangeOrig = $rangeParts[1];
      }

      if ($sizeUnit != 'bytes') {
        throw new RestApiException("Requested Range Not Satisfiable", 416);
      }

      //multiple ranges could be specified at the same time, but for simplicity only serve the first range
      //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
      $rangeOrigParts = explode(',', $rangeOrig, 2);

      $range = '';
      if (isset($rangeOrigParts[0])) {
        $range = $rangeOrigParts[0];
      }

      $extraRanges = '';
      if (isset($rangeOrigParts[1])) {
        $extraRanges = $rangeOrigParts[1];
      }
    }

    $rangeParts = explode('-', $range, 2);

    $filestart = '';
    if (isset($rangeParts[0])) {
      $filestart = $rangeParts[0];
    }

    $fileend = '';
    if (isset($rangeParts[1])) {
      $fileend = $rangeParts[1];
    }

    if (empty($fileend)) {
      $fileend = $filesize - 1;
    } else {
      $fileend = min(abs(intval($fileend)), ($filesize - 1));
    }

    if (empty($filestart) || $fileend < abs(intval($filestart))) {
      // Default: Output filepart from start (0)
      $filestart = 0;
    } else {
      $filestart = max(abs(intval($filestart)), 0);
    }

    if ($filestart > 0 || $fileend < ($filesize - 1)) {
      // Output part of file
      header('HTTP/1.1 206 Partial Content');
      header('Content-Range: bytes ' . $filestart . '-' . $fileend . '/' . $filesize);
      header('Content-Length: ' . ($fileend - $filestart + 1));
    } else {
      // Output full file
      header('HTTP/1.0 200 OK');
      header("Content-Length: $filesize");
    }

    header('Accept-Ranges: bytes');
    // header('Accept-Ranges: 0-'.$filesize);
    set_time_limit(0);
    fseek($openfile, $filestart);
    ob_start();
    while (!feof($openfile)) {
      print(@fread($openfile, (1024 * 8)));
      ob_flush();
      flush();
      if (connection_status() != 0) {
        @fclose($openfile);
        exit;
      }
    }

    @fclose($openfile);
    exit;
  }
}
```

I know, the file-function looks a bit confusing. Mainly the streaming-part is kind of complex to handle.

So let us concentrate on the image-part. I added multiple useful options that help specifying what kind of image you want to get from the server. They are all optional and can be applied as GET-parameters:

| Param         | Value    | Description                                                                       |
| ------------- | -------- | --------------------------------------------------------------------------------- |
| **width**     | int >= 0 | Width of the requested image                                                      |
| **height**    | int >= 0 | Height of the requested image                                                     |
| **maxWidth**  | int >= 0 | Maximum Width, if the original image's resolution is sufficient                   |
| **maxHeight** | int >= 0 | Maximum Height, if the original image's resolution is sufficient                  |
| **cropX**     | int >= 0 | Start-X-position for cropping (crop enabled, if width, height, cropX & cropY set) |
| **cropY**     | int >= 0 | Start-Y-position for cropping (crop enabled, if width, height, cropX & cropY set) |

<a name="versioning"></a>

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/Sebiworld/AppApi/tags).

<a name="license"></a>

## License

This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE.md](LICENSE.md) file for details.

<a name="changelog"></a>

## Changelog

### Changes in 1.0.4 (2020-08-17)

- Usage of `ProcessPageView::pageNotFound`-hook to allow other modules and functions to initialize before the api renders.
- The path to `Routes.php` is now configurable via module's settings
(Thanks to @thomasaull and @spoetnik)

### Changes in 1.0.3 (2020-08-08)

- Bugfix in Router.php (Thanks to @thomasaull)

### Changes in 1.0.2 (2020-07-25)

- Documentation improvements

### Changes in 1.0.1 (2020-07-11)

- Changed all auth-routes to the /auth (or /auth/access) endpoints.
- Updated readme and examples

### Changes in 1.0.0 (2019-08-19)

- Rewritten most of the code
- Setup application management ui
- New authentication methods
