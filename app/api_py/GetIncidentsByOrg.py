import httplib2
import ssl
import json
import Server
from xml.dom.minidom import Node, Document, parseString
from GetMonitoredOrganizations import GetMonitoredOrganizations
import re

MAP={'eventType':'','eventSeverity':'',
'incidentClearedReason':'','phRecvTime':'','phCustId':'',
'hostIpAddr':'','eventName':'','incidentComments':'','incidentSrc':'','eventSeverityCat':''
,'incidentTarget':'','incidentRptIp':'','incidentDetail':'','incidentStatus':'','incidentId':'',
'count':''}

def GetIncidentsByOrg(appServer, user, password, name):
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
		print json.dumps({'error':'Org not found'})
		exit()
	Url="https://"+appServer+"/phoenix/rest/query/"
	Urlfirst=Url+"eventQuery"
	h=httplib2.Http(disable_ssl_certificate_validation=True)
	h.add_credentials(user, password)
	header={'Content-Type':'text/xml'}
	inXml=CreateQueryXML()
	resp, content=h.request(Urlfirst, "POST", inXml, header)
	queryId=content.decode()
	if 'error code="255"' in queryId:
		print "Query Error, check sending XML file."
		exit()
	UrlSecond=Url+"progress/"+queryId
	if resp['status']=='200':
		resp, content=h.request(UrlSecond)
	else:
		print "appServer doesn't return query. Error code is %s" % resp['status']
		exit()
	while content.decode()!='100':
		resp, content=h.request(UrlSecond)

	outXML=[]
	if content.decode()=='100':
		UrlFinal=Url+'events/'+queryId+'/0/1000'
		resp, content=h.request(UrlFinal)
		if content!='':
			outXML.append(content.decode())

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
					UrlFinal=Url+'events/'+queryId+'/'+str(i*1000+1)+'/1000'
					print str(i*1000+1)+'/1000'
					resp, content=h.request(UrlFinal)
					if content!='':
						outXML.append(content.decode())
		else:
			print json.dumps({'error':"no info in this report."})
			exit()

	param=dumpXML(outXML, phCustId, name)

	return param

def CreateQueryXML():

	doc=Document()
	reports=doc.createElement("Reports")
	doc.appendChild(reports)
	report=doc.createElement("Report")
	report.setAttribute("id", "All Incidents")
	report.setAttribute("group", "report")
	reports.appendChild(report)
	name=doc.createElement("Name")
	report.appendChild(name)
	nameText=doc.createTextNode("All Incidents")
	custScope=doc.createElement("CustomerScope")
	custScope.setAttribute("groupByEachCustomer", "true")
	report.appendChild(custScope)
	include=doc.createElement("Include")
	include.setAttribute("all", "true")
	custScope.appendChild(include)
	exclude=doc.createElement("Exclude")
	custScope.appendChild(exclude)
	description=doc.createElement("description")
	report.appendChild(description)
	select=doc.createElement("SelectClause")
	select.setAttribute("numEntries", "All")
	report.appendChild(select)
	attrList=doc.createElement("AttrList")
	select.appendChild(attrList)
	reportInterval=doc.createElement("ReportInterval")
	report.appendChild(reportInterval)
	window=doc.createElement("Window")
	window.setAttribute("unit", "Minute")
	window.setAttribute("val", '180')
	reportInterval.appendChild(window)
	pattern=doc.createElement("PatternClause")
	pattern.setAttribute("window", "3600")
	report.appendChild(pattern)
	subPattern=doc.createElement("SubPattern")
	subPattern.setAttribute("displayName", "Incidents")
	subPattern.setAttribute("name", "Incidents")
	pattern.appendChild(subPattern)
	single=doc.createElement("SingleEvtConstr")
	subPattern.appendChild(single)
	singleText=doc.createTextNode("phEventCategory=1")
	single.appendChild(singleText)
	filter=doc.createElement("RelevantFilterAttr")
	report.appendChild(filter)
	return doc.toxml()

def dumpXML(xmlList, phCustId, name):
	param=[]
	for xml in xmlList:
		doc=parseString(xml)
		for node in doc.getElementsByTagName("events"):
			for node1 in node.getElementsByTagName("event"):
				mapping={}
				for node2 in node1.getElementsByTagName("attributes"):
					for node3 in node2.getElementsByTagName("attribute"):
						itemName=node3.getAttribute("name")
						for node4 in node3.childNodes:
							if node4.nodeType==Node.TEXT_NODE:
								mapping[itemName]=node4.data
					#Cambie la linea de abajo, segunda condicion, original: mapping['phCustId']==phCustId
					if phCustId=="all" or mapping['customer'] == name:
						param.append(mapping)
	return param


def generateResult(param):
	keys = MAP.keys()

	toJsonData = []
	for item in param:
		itemKeys=item.keys()
		value=[]
		for key in keys:
			if key not in itemKeys:
				value.append('no-exist')
			else:
				value.append(item[key])
		mapp = MAP
		index = len(mapp)
		i = 0
		while i < index:
			mapp[keys[i]] = value[i]
			i += 1
		beforeToJSON = json.dumps(mapp)
		toJsonData.append(beforeToJSON)
	return json.dumps(toJsonData)

def FlaskServer(org):
	appServer = Server.appServer
	user = Server.user
	password = Server.password
	param=GetIncidentsByOrg(appServer, user, password, org)
	return generateResult(param)

if __name__=='__main__':
	import sys
	if len(sys.argv) != 2:
		print "Uso: python GetIncidentsByOrg.py nombreOrg"
		exit()
	print FlaskServer(sys.argv[1])
