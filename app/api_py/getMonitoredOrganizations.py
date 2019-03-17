import httplib2
import ssl
import Server
from xml.dom.minidom import Node, Document, parseString

def GetMonitoredOrganizations(appServer, user, password):
Url="https://"+appServer+"/phoenix/rest/config/Domain"
h=httplib2.Http()
h.add_credentials(user, password)
resp, content=h.request(Url, "GET")
outXML=content.decode()
param=dumpXML(outXML)

return param

def dumpXML(xml):
param=[]
doc=parseString(xml)
for node in doc.getElementsByTagName("domains"):
    for node1 in node.getElementsByTagName("domain"):
        xmlId=node1.getAttribute("xmlId")
        if xmlId in ['Domain$system', 'Domain$service',
            'Domain$Super']:
            pass
        else:
            mapping={'name':'', 'domainId':'', 'disabled':'',
            'initialized':'', 'include':'', 'exclude':''}
            for node2 in node1.getElementsByTagName("domainId"):
                for node3 in node2.childNodes:
                    if node3.nodeType==Node.TEXT_NODE:
                        mapping['domainId']=node3.data
            for node4 in node1.getElementsByTagName("excludeRange"):
                for node5 in node4.childNodes:
                    if node5.nodeType==Node.TEXT_NODE:
                        mapping['exclude']=node5.data
            for node6 in node1.getElementsByTagName("includeRange"):
                for node7 in node6.childNodes:
                    if node7.nodeType==Node.TEXT_NODE:
                        mapping['include']=node7.data
            for node8 in node1.getElementsByTagName("name"):
                for node9 in node8.childNodes:
                    if node9.nodeType==Node.TEXT_NODE:
                        mapping['name']=node9.data
            for node10 in node1.getElementsByTagName("disabled"):
                for node11 in node10.childNodes:
                    if node11.nodeType==Node.TEXT_NODE:
                        mapping['disabled']=node11.data
            for node12 in node1.getElementsByTagName("initialized"):
                for node13 in node12.childNodes:
                    if node13.nodeType==Node.TEXT_NODE:
                        mapping['initialized']=node13.data
            param.append(mapping)
    return param
def generateResult(param):

    print "Org Name,Org Id,Disabled,Initialized,Include Range,Exclude Range\n\n"
    for item in param:
        print "%s,%s,%s,%s,%s,%s\n" % (item['name'], item['domainId'],
item['disabled'], item['initialized'], item['include'], item['exclude'])
if __name__=='__main__':
    import sys
    if len(sys.argv)!=4:
        print "Usage: GetMonitoredOrganizations.py appServer user password"
        exit()
    param=GetMonitoredOrganizations(sys.argv[1], sys.argv[2], sys.argv[3])
    generateResult(param)
