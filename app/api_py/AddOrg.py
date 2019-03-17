#!/usr/bin/python
import sys,base64,urllib,urllib2


from xml.dom.minidom import Node,Document,parseString

def restPost(file):
	f = open(file, 'r')
#	appServer = 'fortisiem1.red4g.net'
	appServer = '172.16.3.23'
	user = 'super/control4api'
	password = '!AyL79Lt4FCsEfu4'
	content = f.read()
	f.close()
	url = "https://" + appServer + "/phoenix/rest/organization/add"
	auth = "Basic %s" % base64.encodestring(user + ":" + password)
	request = urllib2.Request(url,content)
	request.add_header('Authorization',auth)
	request.add_header('Content-Type','text/xml') #	'application/xml'
	request.add_header('Content-Length',len(content)+2)
	request.add_header('User-Agent','Python-urllib2/2.7')
	request.get_method = lambda: 'PUT'

	try:
		handle = urllib2.urlopen(request)
	except urllib2.HTTPError, error:
		if (error.code != 204):
			print error

if __name__=='__main__':
	if len(sys.argv) != 2:
		print "Usage: AddOrg.py appServer user password orgDefFile"
		print "Example: python AddOrg.py fortisiem1.red4g.net super/admin pass orgDef.xml"
sys.exit()

#restPost(sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4])
restPost(sys.argv[1])
