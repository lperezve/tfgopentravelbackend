from pprint import pprint
import json

json_data=open("prueba.json")
jdata = json.load(json_data)

def get_keys(dl, keys_list):
	if isinstance(dl, dict):
		keys_list += dl.keys()
		map(lambda x: get_keys(x, key_list), dl.values())
	elif isinstance(dl, list):
		map(lambda x: get_keys(x, keys_list), dl)

keys = []
get_keys(jdata, keys)

for i in keys:
	print (i + '\n')