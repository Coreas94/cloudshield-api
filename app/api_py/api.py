from flask import Flask, Response
import GetMonitoredOrganizations
import GetIncidentsByOrg
import json
app = Flask(__name__)

@app.route("/organizations", methods=['GET'])
def orgs():
	return Response(
		mimetype="application/json",
		response=GetMonitoredOrganizations.FlaskServer(),
		status=200
	)

@app.route('/incidents/<org>', methods=['GET'])
def incidents(org):
	try:
		return Response(
			mimetype="application/json",
			response=GetIncidentsByOrg.FlaskServer(org),
			status=200
		)
	except Exception as e:
		return Response(
			mimetype="application/json",
			response=json.dumps({'error': e.value}),
			status=500
		)