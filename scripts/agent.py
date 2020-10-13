import sys # sys.argv
import time # time.sleep()
import requests # requests.get()
import json # json.load()
import fnmatch # to make machtes "foo/*" == "foo/bar"
import subprocess
from types import SimpleNamespace # json to python object

CONFIG_FILE = 'agent.json'
DEBUG = True
retry = 5000

def truncate(string, at = 27, suffix = '...'):
    return (string[:at] + suffix) if len(string) > at else string

class Event:
    id = "None";
    event = "None";
    data = "None";
    retry = 5000;
    command = "None";

    def __init__(self, event):
        self.event = event

    def trigger(self):
        if DEBUG: 
            print('Triggered event "{event:s}" (id: {id:s}) with data {data:s} => executing command {command:s}'.format(
                event       = self.event,
                id          = self.id,
                data        = truncate(self.dataToJSON()),
                command     = truncate(self.command)
            ))

        self.process = subprocess.Popen(
            ["sh", "-c", self.command],
            env = {
                "ENV_id": str(self.id),
                "ENV_event": str(self.event),
                "ENV_data": self.dataToJSON()
            }
        )

    def isInConfig(self):
        for item in config.listen:
            #print('"%s" == "%s" => %s' % (self.event, item.event, fnmatch.fnmatch(self.event, item.event)))
            #print('"%s" == "%s" => %s' % (self.dataToJSON(), item.data, fnmatch.fnmatch(self.dataToJSON(), item.data)))
            if fnmatch.fnmatch(self.event, item.event) and fnmatch.fnmatch(self.dataToJSON(), item.data):
                self.command = item.command
                return True
        return False

    def dataToJSON(self):
        if isinstance(self.data, str):
            return  self.data
        else:
            return json.dumps(self.data, default=lambda o: o.__dict__)


# get the argument from the command line
try:
    url = sys.argv[1]
except IndexError:
    print('Missing argument.')
    exit(1)

# Parse JSON into an object with attributes corresponding to dict keys.
with open(CONFIG_FILE) as file:
    config = json.load(file, object_hook=lambda d: SimpleNamespace(**d))

if DEBUG:
    for item in config.listen:
        print('Listening for event "{0:s}" with data "{1:s}".'.format(item.event, item.data))

if DEBUG: print("Starting event stream...")

eventFullyRecieved = False
last_line = None

while True:
    r = requests.get(url, stream=True)
    # iterate over lines of output
    for line in r.iter_lines():
        decoded_line = line.decode('utf-8')
        #if DEBUG: print(decoded_line) # prints EVERY line that is sent via http(s)
        if decoded_line == "0": eventFullyRecieved = False
        if decoded_line == "": eventFullyRecieved = False
        if decoded_line == "" and last_line != "0": eventFullyRecieved = True
        if decoded_line != "0" and decoded_line != "":
            key, value = decoded_line.split(":", 1)
            key = key.strip()
            value = value.strip()
            
            if key == "event":
                event = Event(value)
                eventFullyRecieved = False
            if key == "id":
                event.id = int(value)
                eventFullyRecieved = False
            if key == "data":
                try: event.data = json.loads(value, object_hook=lambda d: SimpleNamespace(**d)).data
                except json.decoder.JSONDecodeError: event.data = value
                eventFullyRecieved = False
            if key == "retry":
                event.retry = int(value)
                retry = int(value)
                eventFullyRecieved = False

        if eventFullyRecieved and event.isInConfig():
            event.trigger()

        last_line = decoded_line

    if DEBUG: print('Sleeping {0:0.2f} seconds ...'.format(retry/1000))
    time.sleep(retry/1000)
