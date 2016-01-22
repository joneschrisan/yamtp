#YAMTP
The Yet Another Message Transfer Protocol.

##Table of contents

[1: About](#1about)

[2: Definition](#2definition)

[2.1: Requests](#21requests)

[2.1.1: Stream Headers](#211streamheaders)

[2.1.1.1: Methods](#2111methods)

[2.1.1.2: Pages](#2112pages)

[2.1.1.3: Protocols and Versions](#2113protocolsandversions)

[2.1.1.4: Host](#2114host)

##1: About

YAMTP (pronounced yam-t-p) endevours to create a simple yet expandable message protocol for comunication between servers of multiple different types and using multiple diferent languages.

Following in this document we will set out a definition to be used when creating, sending and receiving messages, and when creating clients and servers.

YAMTP is loosely bases on the HTTP 1.1 (rfc2616) protocol (found [here](https://www.w3.org/Protocols/rfc2616/rfc2616.html)) and as such is almost 100% compatible with it.
 You can use a HTTP server to serve YAMTP requests, with only one additional non standard HTTP header for instance and a wrapper function to pass the request to a request handeler.
 However, you can not use a YAMTP server to serve web pages.

!***NOTE: This definition has not yet been finished and finalized. As such it may (and probably will) change without warning. A version number will be added when the definition has been finalized!***!

##2: Definition

**YAMTP should be used with the following constraints.**

Any transport method can be used in reality so long as the payload and the form headers are passed to the request handeler are kept the same.

**ALL requests should be transmitted encrypted via ssl. NEVER under any circumstance should a transfer take place as plain text.**

All lines should end with ```CRLF``` (```\r\n```) NOT ```CR``` (```\r```) or ```LF``` (```\n```).

Sessions should NEVER be used. Please see the authentication section for mor detail.

###2.1: Requests:

The data stream should consist of:
```
Headers\r\n
\r\n
Content Data\r\n
\r\n
```

####2.1.1: Stream Headers
```
[method] [path to page] YAMTP/[version]\r\n
Host: [remote hostname]\r\n
Connection: [connection type]\r\n
Content-Type: text/json\r\n
Content-Length: [length of data in bytes]\r\n
\r\n
```

***NOTE: When using HTTP the first line should be*** ```POST [path to page] HTTP/1.1\r\n``` ***and the extra HTTP header*** ```YAMTP-Method: [mothod]\r\n``` ***should be set***

#####2.1.1.1: Methods

Methods are the same as HTTP methods and should only be used for the following reasons:

| Name | Description |
| --- | --- |
| GET | Used to retrieve information from the server. |
| POST | Used to send general info to the server. |
| PUT | Used to save data to the server (Add a record to a database) |
| UPDATE | Used to update data on the server (Update a record in a database) |
| DELETE | Used to delete data from the server (Delete a record from a database) |

#####2.1.1.2: Pages

The page ([path to page] in the header stream example [above](#211streamheaders)) should be set to the a file path to the file being called relative to the base dir or document root of the server.

**Eg:**
* If the base dir is ```/var/YAMTP``` and the file requested is *foo.yamtp* located on the file system at ```/var/YAMTP/foo.yamtp``` then the path to page shold be ```/foo.yamtp```
* If foo.yamtp in inside a sub directory of foobar on the file system then the path to page should be ```/foobar/foo.yamtp```
* The same goes for document root if using a web server to host a YAMTP service.

#####2.1.1.3: Protocols and Versions

The protocol name for a YAMTP request is *YAMTP*, this should be replaced with *HTTP* if sending a request to a web server.

The version number is the version of the protocol you are using. ```0.0.03``` for This version of the YAMTP definition. For HTTP ```1.1``` is the standard we have based YAMTP on.

The protocol and version should be seperated with a ```/```.

#####2.1.1.4: Host



***Changing the rest of this file***

Authentication should be done on the server side with details passed to the server on each request when required within a message.

For multiple requests with different headers send an array of requests.

Empty value should not indicate false, and the same in repect that a value of any kind should not indicate a true value.

Data MUST be either URL safe, or BASE64 or UU encoded. If encoded then the enc property of the request must be set.

Valid values for enc are null, URL, BASE64 or UU.

If no encoding has been done then the enc value should be null.

If authentication is used then message MUST be of type object and MUST contain all a property of "authentication" which in tern contains all authentication data required by the server to make the request.

On auth fail the server MUST respond with a failed message. Response data MUST NEVER say exactly why the authentication has failed but can be vague.

Eg.

OK: "Sorry the username and password did not match our records"

BAD: "The username could not be found". | "The password was incorrect for the username given"

When deleting records from the server the data should not actually be deleted but instead some means of telling subsiquent requests that that data has been deleted.

In a database example this could be a flag or a field set to say that record has been deleted.

Never use POST/GET/DELETE to add or update data.

Never use PUT to update data. If data already exists then return false/failed message.

Never use UPDATE to add data. If data does not exist then return false/failed message.

Response messages MUST be of type object and MUST contain a property "passes" with a boolean value of true or false, 1 or 0.

Extra properties may be included if data is required to be passed back to the client.

Data passed back in the message can be of any type.

Functions MUST NEVER be sent in message requests or responses. Function names can be, and then used to call functions on the server or client.

Request

{
    "headers"   : {
        "mime"      : "text/plain",
        "enc"       : null,
        "auth"      : false
    },
    "message"   : [],
    "callback"  : null
}
Name	Type	Default	Required	Description
headers	Object	Object of headers	Yes	An Object of headers to be sent with the request. (Not to be confused with HTTP headers)
mime	String	text/plain	Yes	A string containing the mime type of the message(s) being sent.
enc	String	NULL	Yes	A string containing the encoding type of the mesasge(s) being sent.
auth	Boolean	FALSE	Yes	Boolean, set if the message sent is required to be authed on the recieving end.
message	Mixed	Enpty Array	Yes	A String message or an array of mixed messages to be sent.
callback	String	NULL	No	A string containing a function name to be used with jsonp requests as a callback.
