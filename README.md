# PhAnviz - PHP Client for Anviz devices. 
This is "limited" implementation of CommsProtocol for Anviz EP300 devices. 
Limited because it does not contain functions for each possible command, but just provides a general way to send commands and process responses in proper way.

## Why I created another one client for Anviz devices
Initially I tried to use Jerko Tisler's [jtisler/PHPAnviz](https://github.com/jtisler/PHPAnviz/)  library. That is great piece of work, but I found it a bit complicated due to dependency on gearman and it should have *anviz-server* app running. 
Finally it requires Anviz device to operate in client mode and I think the way Jerko offered is good for handling connections from Anviz when it operates in client mode. 
I just was need simple "syncronous" client application running by cron, which will send commands and save T&A records to database.

Another reason is that I detected some unexpected stuff in responses from Anviz. It still was valid responses, but with additional pieces. After parsing I found that those pieces are coming with `0xDF` acknowlendge code, which is described in Comms protocol as "Sent T&A record in real time", but no idea why these pieces are appearing in response for many different commands. 

**Example:** 
I'm sending *Get record information* command (code *0x3C*) and expecting response with ACK code equal to *0xBC*. Like below: 

```
STX	CH		ACK	RET	LEN	DATA					CRC16
---------------------------------------------------------------------------------------------
a5	00000001	bc	00	0012	00002700002b000002000000004e98000001	d37b
```

But sometimes response may contain additional pieces with *0xDF* ACK code. Like this: 

```
STX	CH		ACK	RET	LEN	DATA					CRC16
----------------------------------------------------------------------------------------------
a5	00000001	bc	00	0012	00002700002b000002000000004e98000001	d37b
a5	00000001	df	00	000e	0000000023259f8c9c0103000000		9760
a5	00000001	df	00	000e	0000000020259f8ca60103000000		20fa
a5	00000001	df	00	000e	0000000025259f8cc30103000000		adf5
a5	00000001	df	00	000e	000000001a259f8ccb0103000000		ce07
a5	00000001	df	00	000e	0000000026259f8cd50103000000		8edb
a5	00000001	df	00	000e	0000000018259f8cda0103000000		1b19
a5	00000001	df	00	000e	000000001f259f8cdd0103000000		3fc1
a5	00000001	df	00	000e	0000000019259f8ce40203000000		82d6
``` 
If anyone have idea why it's happening I have created [stackoverflow question for it](https://stackoverflow.com/questions/59528983/anviz-ep300-unexpected-data-in-response-of-get-record-information-command-0x3c)

I can conclude that Anviz sends "real-time" TA records (pieces with *0xDF*) when socket created for sending other commands.  

The worst thing is that it does not return that TA records when we trying to fetch "new" TA records. It causes that some TA records may be lost by client. In order to overcome it I implemented "callback driven" processing of commands' responses and it allows to collect TA records from *0xDF* pieces in responses for any command. 

