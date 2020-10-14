import sys # sys.argv
import time # time.sleep()
import requests # requests.get()
import json # json.load()
import fnmatch # to make machtes "foo/*" == "foo/bar"
import subprocess # subprocess.Popen(), subprocess.check_output()
from types import SimpleNamespace # json to python object

CONFIG_FILE = 'agent.json'
INFO_FILE = '/info'
INFO_FILE_URL = 'actionConfig'
DEBUG = True
VERBOSE = True
retry = 5000

def get_info(variable, file = INFO_FILE):
    cmd = 'set -a; source "{file}"; set +a; printf "%s" "${variable}"'.format(file = INFO_FILE, variable = variable)
    string = subprocess.check_output(['bash', '-c', cmd]).decode(sys.stdout.encoding)
    return string if string != "" else False

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
        if VERBOSE:
            print('Triggered event "{event:s}" (id: {id:s}) with data "{data:s}"" => executing command "{command:s}"'.format(
                event       = self.event,
                id          = self.id,
                data        = truncate(self.dataToJSON()),
                command     = truncate(self.command)
            ))

        env = {
            "ENV_id": str(self.id),
            "ENV_event": str(self.event),
            "ENV_data": self.dataToJSON()
        }
        if DEBUG: print('DEBUG> trigger(): spawning process "{command:s}" with environment "{env:s}"'.format(
                command = self.command,
                env     = str(env)
            ))
        self.process = subprocess.Popen(
            ["sh", "-c", self.command],
            env = env
        )

    def isInConfig(self):
        if hasattr(config, 'listen'):
            for item in config.listen:
                if DEBUG: print('DEBUG> isInConfig(): "%s" ~= "%s" => %s' % (self.event, item.event, fnmatch.fnmatch(self.event, item.event)))
                if DEBUG: print('DEBUG> isInConfig(): "%s" ~= "%s" => %s' % (self.dataToJSON(), item.data, fnmatch.fnmatch(self.dataToJSON(), item.data)))
                if fnmatch.fnmatch(self.event, item.event) and fnmatch.fnmatch(self.dataToJSON(), item.data):
                    self.command = item.command
                    return True
        return False

    def dataToJSON(self):
        if isinstance(self.data, str):
            return  self.data
        else:
            return json.dumps(self.data, default=lambda o: o.__dict__)

# Parse JSON into an object with attributes corresponding to dict keys.
with open(CONFIG_FILE) as file:
    config = json.load(file, object_hook=lambda d: SimpleNamespace(**d))

config.token = get_info('token')
if config.token == False:
    print('Variable "${variable}" not found in {file}.'.format(variable = 'token', file = INFO_FILE))
    exit(1)

url = get_info(INFO_FILE_URL)
if url == False:
    print('Variable "${variable}" not found in {file}.'.format(variable = INFO_FILE_URL, file = INFO_FILE))
    exit(1)

# get the token from command line if set
try: config.token = sys.argv[1]
except IndexError: pass

if not hasattr(config, 'listen'):
    print('No events to listen to in config.')
    exit(1) 

if VERBOSE:
    for item in config.listen:
        print('Listening for event "{0:s}" with data "{1:s}".'.format(item.event, item.data))

if VERBOSE: print("Starting event stream...")

url = url.replace('ticket/config', 'event/agent', 1)
url = url.format(token = config.token)
eventFullyRecieved = False
last_line = None
event = Event("None")

while True:
    if VERBOSE: print('Getting URL {0:s}.'.format(url))
    r = requests.get(url, stream=True)
    if DEBUG: print('DEBUG> status code {code:d}'.format(code = r.status_code))
    if VERBOSE and r.status_code == 409: print('Conflict: The ressource is exclusively locked, trying again later.')
    if VERBOSE and r.status_code == 200: print('Stream successfully started.')
    if r.status_code != 200: retry = 5000

    # iterate over lines of output
    for line in r.iter_lines():
        decoded_line = line.decode('utf-8')
        if DEBUG: print('DEBUG>> {line:s}'.format(line = decoded_line))
        if r.status_code == 200:
            if decoded_line == "0": eventFullyRecieved = False
            if decoded_line == "": eventFullyRecieved = False
            if decoded_line == "" and last_line != "0": eventFullyRecieved = True
            if decoded_line != "0" and decoded_line != "":
                try: key, value = decoded_line.split(":", 1)
                except ValueError: key, value = decoded_line, ""
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

    if VERBOSE: print('Sleeping {0:0.2f} seconds ...'.format(retry/1000))
    time.sleep(retry/1000)
