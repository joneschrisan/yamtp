#YAMTP
The Yet Anpother Message Transfer Protocol

##About

YAMTP (pronounced yam-t-p) endevers to create a simple yet expandable message protocol for comunication between servers of multiple different types and using multiple diferent languages

##Definition

YAMTP should be used with the following constraints.

A request should be transfered over the HTTP 1.1 protocol and follow the definition provided by W3C (https://www.w3.org/Protocols/rfc2616/rfc2616.html)

All requests should be transmitted using SSL (HTTPS)

HTTP Methods should be used and for the following reasons only.

| Name | Description |
| --- | --- |
| GET | Used to retrieve information from the server. |
| POST | Used to send general info to the server. |
| PUT | Used to save data to the server (Add a record to a database) |
| UPDATE | Used to update data on the server (Update a record in a database) |
| DELETE | Used to delete data from the server (Delete a record from a database) |

HTTP auth should never be used. Instead authentication should be done on the server side with details passed to the server on each request when required within a message

For multiple requests with different headers and/or authentication send an array of requests.

Empty value should not indecate false, and the same in repect that a value of any kind should not indecate a true value.

Data MUST be either URL safe, or BASE64 or UU encoded. If encoded then the enc property of the request must be set.

Valid values for enc are null, URL, BASE64 or UU.

If no encoding has been done then the enc value should be null.
 
If authentication is used then message MUST be of type object and MUST contain all a property of "authentication" which in tern contains all authentication data required by the server to make the request.

On auth fail the server MUST respond with a failed message. Response data MUST NEVER say exactly why the authentication has failed but can be vague.
#####Eg.
```
OK:		"Sorry the username and password did not match our records"
BAD:	"The username could not be found". | "The password was incorrect for the username given"
```
 
When deleting records from the server the data should not actually be deleted but instead some means of telling subsiquent requests that that data has been deleted.
#####In a database example this could be a flag or a field set to say that record has been deleted.
```
Never use POST/GET/DELETE to add or update data.
Never use PUT to update data. If data already exists then return false/failed message.
Never use UPDATE to add data. If data does not exist then return false/failed message.
```

Response messages MUST be of type object and MUST contain a property "passes" with a boolean value of true or false, 1 or 0.

Extra properties may be included if data is required to be passed back to the client.

Data passed back in the message can be of any type.

Functions MUST NEVER be sent in message requests or responses. Function names can be, and then used to call functions on the server or client.

###Request
```json
{
	"headers"	: {
		"mime"		: "text/plain",
		"enc"		: null,
		"auth"		: false,
		"type"		: "get"
	},
	"message"	: [],
	"callback"	: null
}
```


| Name | Type | Default | Required | Description |
| --- | --- | --- | --- | --- |
| headers | Object | Object of headers | Yes | An Object of headers to be sent with the request. (Not to be confused with HTTP headers) |
| mime | String | text/plain | Yes | A string containing the mime type of the message(s) being sent. |
| enc | String | NULL | Yes | A string containing the encoding type of the mesasge(s) being sent. |
| auth | Boolean | FALSE | Yes | Boolean, set if the message sent is required to be authed on the recieving end. |
| type | String | get | Yes | The message protocol to use. get, post, put, update, delete. |
| message | Mixed | Enpty Array | Yes | A String message or an array of mixed messages to be sent. |
| callback | String | NULL | No | A string containing a function name to be used with jsonp requests as a callback. |

```
If callback is omitted then same domain request restrictions apply.
```

##Examples
###Send message

####1 Send a string message.
```json
{
  "headers": {
    "mime"		: "text/plain",
		"enc"		: null,
		"auth"		: false,
		"type"		: "get"
  },
	"message"	: "foobar"
}
```

####2 Send an array of messages
```json
{
  "headers": {
    "mime"		: "text/plain",
		"enc"		: null,
		"auth"		: false,
		"type"		: "get"
  },
	"message"	: [
		"foo",
		"bar"
	]
}
```
####3 Send an object message
```json
{
  "headers": {
    "mime"		: "text/plain",
		"enc"		: null,
		"auth"		: false,
		"type"		: "get"
  },
	"message"	: {
		"foo" : "bar"
	}
}
```

####4 Send an array of requests with diferent authentication types
```json
[
	{
	  "headers": {
      "mime"		: "text/plain",
  		"enc"		: null,
  		"auth"		: false,
  		"type"		: "get"
    },
		"message"	: "foo"
	},
	{
	  "headers": {
      "mime"		: "text/plain",
  		"enc"		: null,
  		"auth"		: true,
  		"type"		: "get"
    },
		"message"	: "bar"
	}
]
```

###Return message

####1 Passed message
```json
{
	"message"	: {
		"passed" : true
	}
}
```

####2 Passed message with data
```json
{
	"message"	: {
		"passed"	: false,
		"data"		: {
			"foo" : "bar"
		}
	}
}
```
