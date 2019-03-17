#!/usr/bin/env python
import httplib2
import ssl
import re
import Server
import json
from xml.dom.minidom import Node, Document, parseString, parse
from GetMonitoredOrganizations import GetMonitoredOrganizations

def GetQueryResultByOrg(appServer, user, password, name, inXml, starttime, endtime):
	#translate orgName to phCustId
	phCustId=''
	if name.lower()=="all":
		phCustId="all"
	elif name.lower()=="super":
		phCustId="1"
	else:
		orgs=GetMonitoredOrganizations(appServer, user, password)
		for item in orgs:
			if name==item['name']:
				phCustId=item['domainId']
	if phCustId=='':
		print json.dumps({'error':"Org %s is not exist. Exit." % name})
		exit()

	Url="https://"+appServer+":443/phoenix/rest/query/"
	Urlfirst=Url+"eventQuery"
	h=httplib2.Http(disable_ssl_certificate_validation=True)
	h.add_credentials(user, password)
	header={'Content-Type':'text/xml'}
	if '.xml' not in inXml:
		inXml +='.xml'

	doc=parse(inXml)
	t=doc.toxml()

	t = t.replace('::starttime', starttime)
	t = t.replace('::endtime', endtime)

	if '<DataRequest' in t:
		t1=t.replace("<DataRequest", "<Reports><Report")
	else:
		t1=t
	if '</DataRequest>' in t1:
		t2=t1.replace("</DataRequest>", "</Report></Reports>")
	else:
		t2=t1

	resp, content=h.request(Urlfirst, "POST", t2, header)
	queryId=content.decode("utf-8")
	if 'error code="255"' in queryId:
		print json.dumps({'error':"Query Error, check sending XML file."})
		exit()

	UrlSecond=Url+"progress/"+queryId
	if resp['status']=='200':
		resp, content=h.request(UrlSecond)
	else:
		print json.dumps({'error':"appServer doesn't return query. Error code is %s" % resp['status']})
		exit()
	while content.decode("utf-8")!='100':
		resp, content=h.request(UrlSecond)

	outXML=[]
	if content.decode("utf-8")=='100':
		UrlFinal=Url+'events/'+queryId+'/0/800'
		resp, content=h.request(UrlFinal)
		if content!='':
			outXML.append(content.decode("utf-8"))

		p=re.compile('totalCount="\d+"')
		mlist=p.findall(content)
		if mlist[0]!='':
			mm=mlist[0].replace('"', '')
			m=mm.split("=")[-1]
			num=0
			if int(m)>1000:
				num=int(m)/1000
				if int(m)%1000>0:
					num+=1
			if num>0:
				for i in range(num):
					UrlFinal=Url+'events/'+queryId+'/0/800'
					#'+str((i+1)*1000)+'
					#print str(i*1000+1)+'/1000'
					resp, content=h.request(UrlFinal)
					if content!='':
						outXML.append(content.decode("utf-8"))
		else:
			print json.dumps({'error':"no info in this report."})
			exit()
	data=dumpXML(outXML, phCustId)
	return data

def dumpXML(xmlList, phCustId):
	param=[]
	for xml in xmlList:
		doc=parseString(xml.encode('ascii', 'xmlcharrefreplace'))
		for node in doc.getElementsByTagName("events"):
			for node1 in node.getElementsByTagName("event"):
				mapping={}
				for node2 in node1.getElementsByTagName("attributes"):
					for node3 in node2.getElementsByTagName("attribute"):
						itemName=node3.getAttribute("name")
						for node4 in node3.childNodes:
							if node4.nodeType==Node.TEXT_NODE:
								message=node4.data
								if '\n' in message:
									message=message.replace('\n', '')
								mapping[itemName]=message
					if 'phCustId' not in mapping:
						param.append(mapping)
					else:
						if phCustId=="all" or mapping['phCustId']==phCustId:
							param.append(mapping)
	return param

def generateResult(param):
	if len(param)==0:
		print json.dumps({'error':"No records found. Exit"})
		exit()
	else:
		#print "Total records %d" % len(param)
		keys=param[0].keys()
		toJsonData = []
		#print ','.join(keys)
		for item in param:
			itemKeys=item.keys()
			value=[]
			for key in keys:
				if key not in itemKeys:
					value.append('no-exist')
				else:
					value.append(item[key])
			#print ','.join(value)
			mapp = param[0]
			index = len(mapp)
			i = 0
			while i < index:
				mapp[keys[i]] = value[i]
				i += 1
			beforeToJSON = json.dumps(mapp)
			toJsonData.append(beforeToJSON)
		return json.dumps(toJsonData)

def FlaskServer(xml,s,e):
	appServer = Server.appServer
	user = Server.user
	password = Server.password
	data=GetQueryResultByOrg(appServer,user, password, 'all', xml, s, e)
	return generateResult(data)

if __name__=='__main__':
	import sys
	if len(sys.argv)!=4:
		print json.dumps({'alert':"Uso: getCritical.py inputXML starttime endtime"})
		exit()
	print FlaskServer(sys.argv[1], sys.argv[2], sys.argv[3])
