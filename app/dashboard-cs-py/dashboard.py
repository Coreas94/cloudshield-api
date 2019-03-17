#!/usr/bin/env python
# -*- coding: utf-8 -*-

# Dependencias:
#   $ sudo pip install git+https://github.com/dpallot/simple-websocket-server.git

from SimpleWebSocketServer import SimpleWebSocketServer, WebSocket
from time import gmtime, strftime
from datetime import datetime
import urllib2
import json
import sys

clients = []
class DashboardSocket(WebSocket):
    def writeLog(self, status):
        time = strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime())
        log = "cliente {}: {} {}\n".format(status, self.address, time)
        file = open('logger.log', 'a')
        file.write(log)
        file.close()

    def handleMessage(self):
        for client in clients:
            if client == self:
                arg = sys.argv
                url = "http://{}/cloudshield_api/api/v2/fortisiem/get_logs?token={}".format(arg[1], self.data)
                try:
                    contents = urllib2.urlopen(url).read()
                    parsed = json.loads(contents)
                    information = parsed['success']['data']

                    Dates = []
                    Events = []
                    IPSource = []
                    Category = []
                    IPDestination = []

                    for reg in information:
                        IPDestination.append(reg['dst_ip'])
                        IPSource.append(reg['src_ip'])
                        Dates.append(reg['receive_time'])
                        Events.append(reg['event_name'])
                        Category.append(reg['severity_category'])

                    sortBlobDestination     = self.eventSortAddresses(IPDestination)    # IP más atacada
                    sortBlobSource          = self.eventSortAddresses(IPSource)         # IP más atacante
                    sortBlobEvent           = self.eventSortAddresses(Events)           # Amenazas bloqueadas
                    attacksCount            = self.filterTodayAttacks(Dates)            # Ataques diarios
                    severityCount           = self.countCategories(Category)            # Severidad de ataques
                    localidadesEvents       = self.filtrarLocalidades(information)

                    response = json.dumps({
                        'atacada': sortBlobDestination[0],
                        'atacante': sortBlobSource[0],
                        'ataques_diarios': attacksCount,
                        'eventos': sortBlobEvent[0:5],
                        'categoria_ataques': severityCount,
                        'localidades_ataques': localidadesEvents
                    })
                    self.sendMessage(unicode(response))
                except Exception:
                    response = json.dumps({
                        'atacada': {
                            'count': 0,
                            'event': '1.1.1.1'
                        },
                        'atacante': {
                            'count': 0,
                            'event': '1.1.1.1'
                        },
                        'ataques_diarios': 0,
                        'eventos': [],
                        'categoria_ataques': {
                            'low': 0,
                            'high': 0,
                            'medium': 0,
                            'critical': 0
                        },
                        'localidades_ataques': [],
                        'error_code': True
                    })
                    self.sendMessage(unicode(response))
    def countCategories(self, array):
        low = 0
        medium = 0
        high = 0
        critical = 0
        for cat in array:
            if cat == "LOW": low += 1
            if cat == "MEDIUM": medium += 1
            if cat == "HIGH": high += 1
            if cat == "CRITICAL": critical += 1
        return {
            'low': low,
            'high': high,
            'medium': medium,
            'critical': critical
        }

    def filtrarLocalidades(self, array):
        blacklist = []
        localidades = []
        for lo in array:
            srcLongitude = lo['src_longitude']
            dstLongitude = lo['dst_longitude']
            srcLatitude = lo['src_latitude']
            dstLatitude = lo['dst_latitude']

            dstLabel = lo['dst_country']
            srcLabel = lo['src_country']

            if srcLongitude != 'no-exist' and srcLongitude != 'undefined':
                if srcLatitude != 'no-exist' and srcLatitude != 'undefined':
                    if dstLongitude != 'no-exist' and dstLongitude != 'undefined':
                        if dstLatitude != 'no-exist' and dstLatitude != 'undefined':
                            if len(localidades) > 0:
                                flag = True
                                for point in localidades:
                                    source = point['points_source']
                                    destination = point['points_destination']

                                    src_lat = source[0]
                                    src_lng = source[1]
                                    dst_lat = destination[0]
                                    dst_lng = destination[1]

                                    if src_lat == srcLatitude and dst_lat == dstLatitude and src_lng == srcLongitude and dst_lng == dstLongitude:
                                        flag = False
                                        break
                                if flag:
                                    localidades.append({
                                        'country_destination': dstLabel,
                                        'country_source': srcLabel,
                                        'points_source': [srcLatitude, srcLongitude],
                                        'points_destination': [dstLatitude, dstLongitude]
                                    })
                            else:
                                localidades.append({
                                    'country_destination': dstLabel,
                                    'country_source': srcLabel,
                                    'points_source': [srcLatitude, srcLongitude],
                                    'points_destination': [dstLatitude, dstLongitude]
                                })
        return localidades[0:10]

    def filterTodayAttacks(self, array):
        dataDates = []
        now = datetime.now()
        for dates in array:
            date = datetime.strptime(dates, r"%Y-%m-%d %H:%S:%M")
            if now.date() == date.date(): dataDates.append(date)
        return len(dataDates)

    def eventSortAddresses(self, array):
        return list(reversed(sorted(self.groupEventAddress(array))))

    def groupEventAddress(self, array):
        blacklist = []
        for ip in array:
            if self.isEventArray(blacklist, ip) == False:
                if ip != 'no-exist' and ip != 'undefined': blacklist.append(ip)
        IPCounter = []
        for b in blacklist:
            IPCounter.append({
                'event': b,
                'count': self.eventCount(array, b)
            })
        return IPCounter

    def eventCount(self, array, ip):
        length = 0
        for i in array:
            if i == ip: length += 1
        return length

    def isEventArray(self, array, ip):
        flag = False
        for addr in array:
            if addr == ip:
                flag = True
                break
        return flag

    def handleConnected(self):
        self.writeLog('conectado')
        clients.append(self)

    def handleClose(self):
        self.writeLog('desconectado')
        clients.remove(self)

if "__main__" == __name__:
    if len(sys.argv) == 1:
        print "<IP Host> not found"
        exit
    else:
        server = SimpleWebSocketServer('', 4320, DashboardSocket)
        server.serveforever()
