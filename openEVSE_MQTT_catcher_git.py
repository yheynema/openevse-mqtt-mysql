#!/usr/bin python
# Script de logging de session de consommation d'energie pour openEVSE. L'idee de ce script est de
# logguer toutes les sessions de fin de recharge via la borne openEVSE. Ceci afin de faciliter la
# refacturation d'electricite.
#
# Par: Y Heynemand - Nov 2018

# Adaptation a partir du travail de matbor, sur github, comme suit:
# September 2013 - by Matthew Bordignon, @bordignon on Twitter
# Simple Python script (v2.7x) that subscribes to a MQTT broker topic and inserts the topic into a mysql database
# This is designed for the http://mqttitude.org/ project backend
#
# Ainsi que de:
# Details At: https://iotbytes.wordpress.com/store-mqtt-data-from-sensors-into-sql-database/
#
# La raison du merge est que le premier utilise MySQL pour logger mais pas la librairie Paho,
# qui semble mieux standardisee que mosquitto. Le second n'utilise pas MySQL mais avec Paho.
#
# 1er mars 2020: ajout du logger

import time
import json
import paho.mqtt.client as mqtt
import mysql.connector
from mysql.connector import errorcode
import glob
import logging
import logging.handlers

LOG_FILENAME = 'OpenEVSE-logger.out'

M_VERSION = "2.3.1"

M_INFO = 2                 # Print messages: 0 - none, 1 - Error/Critial, 2 - status Info, 3 - Debug


# MQTT Settings
MQTT_Broker = "AA.BB.CC.DD"
MQTT_Port = 1883
Keep_Alive_Interval = 45
MQTT_Topics = [("openevse/state", 0), ("openevse/wh", 0)]

# mysql config
sql_cfg = {
    'user': 'DB_USER',
    'password': 'DB_PASSWD',
    'host': 'localhost',
    'database': 'mqtt',
    'raise_on_warnings': True
}
mysql_table = "energySession"
# id,tstamp,value,sdur,carid,status
# create table energySession (
#  id INT NOT NULL AUTO_INCREMENT,
#  tstamp INT unsigned,
#  value INT unsigned,
#  sdur INT unsigned,
#  carid tinyint unsigned,
#  status tinyint null default 0,
#  tarifid int default 0,
#  PRIMARY KEY (id)
# );

# Global status variables
currentState = 0
lastState = 0
logEnergyState = 0
sessionStartAt = 0
sessionEndedAt = 0
previousWsReading = 0
initialWsReading = 0
state2Timestamp = 0


def my_info(type, message):
    if M_INFO >= type:
#        print(message)
         logger.info(message);

# Subscribe to all Sensors at Base Topic


def on_connect(mosq, obj, rc):
    with mqttc:
        mqttc.subscribe(MQTT_Topic)
        logger.debug("rc: "+str(rc))


def on_message(mosq, obj, msg):
    global currentState
    global lastState
    global logEnergyState
    global sessionStartAt
    global sessionEndedAt
    global previousWsReading
    global initialWsReading
    global state2Timestamp

    logger.debug("On "+time.ctime()+":"+str(msg.topic) +
          " "+str(msg.qos)+" "+str(msg.payload))
    if msg.topic == 'openevse/state':
        currentState = int(msg.payload)
        logger.debug("CurrSt="+str(currentState)+" lastState="+str(lastState))
        if currentState != lastState:
            logger.info('On '+time.ctime()+' - State changed: last='+str(lastState) +
                  " new="+str(currentState))
            # Dectecting state transition from (0,1) to 2, connected, a new session opens:
            if lastState <= 1 and currentState == 2:
                # just connected
                state2Timestamp = time.time()
            # Detecting state transition from 2 to 3, charging session
            if lastState == 2 and currentState == 3:
                sessionStartAt = time.time()
                # A new session is when the charging station is plugged to the car, up to when it get unplugged.
                # While plugged, the car can decide to go in charge mode or not, and back and forth.
                # This needs to be tracked as the charging station computes total energy while connected (ie, the session)
                # I need to track the delta sessions for energy and duration.
                # So the following detectes if the car just been connected, reading the energy is different.
                if (sessionStartAt - state2Timestamp) < 32:
                    logEnergyState = 1
                else:
                    logEnergyState = 2
                logger.info('Session started at: '+str(sessionStartAt))
            # Decting end of charge session, moving from 3 down to 1 or 2:
            if currentState < lastState and currentState < 4 and currentState > 0:
                if lastState == 3:
                    logEnergyState = lastState
                    sessionEndedAt = time.time()
                    logger.info('Session ended at: '+str(sessionEndedAt))
            lastState = currentState
    if msg.topic == 'openevse/wh':
        # Read data in Watt-Second:
        currentWsReading = long(float(msg.payload))
        logger.debug('logEnergyState status: '+str(logEnergyState))
        # This session state went from disconnect to charging, thus a NEW session detected:
        if logEnergyState == 1:
            initialWsReading = 0
            logEnergyState = 0
            logger.info('New session detected.')
        # This session state went from connected to charging, additional charge while being connected
        # In other words, this is a Delta session. The previousWsReading handling is important as when
        # the state transition, we can get the Ws reading 30 sec later, some consumption might not
        # be considered. The code addresses that situation.
        if logEnergyState == 2:
            if previousWsReading == 0:
              #Detected  delta, but not previousWsReading... odd! Let's take current reading:
              initialWsReading = currentWsReading
            else:
              #This is better, normal, consider the last reading, just before transition from state 2 to 3:
              initialWsReading = previousWsReading
            logEnergyState = 0
            logger.info('Delta session detected. Using initialWsReading as:'+str(initialWsReading))
        # End of session detected moved from charging down to either connected or disconnected, let's log the session details:
        if logEnergyState == 3:
            if currentWsReading > 0:
                sessionTime = int(sessionEndedAt - sessionStartAt)
                energySession = int(currentWsReading - initialWsReading)
                logger.info('Session duration: '+str(sessionTime))
                logger.info('Session Energy: '+str(energySession))
                if energySession > 0:
                    try:
                        logger.debug('Logging data to MySQL')
                        cnx = mysql.connector.connect(**sql_cfg)
                        cursor = cnx.cursor()
                        queryText = ("INSERT INTO energySession "
                            "(tstamp,value,sdur,carid,status) "
                            "VALUES (%s, %s, %s, %s, %s)")
                        queryArgs = (time.time(), energySession,sessionTime, 1, 0)
                        cursor.execute(queryText, queryArgs)
                        logger.info('Successfully Added record to mysql')
                        # Make sure data is committed to the database
                        cnx.commit()
                        cursor.close()
                        cnx.close()
                        logEnergyState = 0
                    except mysql.connector.Error as err:
                        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
                          logger.critical("Something is wrong with your user name or password")
                        elif err.errno == errorcode.ER_BAD_DB_ERROR:
                          logger.critical("Database does not exist")
                        else:
                          logger.error(err)
                        logger.error("Error encountered with database.")
                    my_info(3,'Did the job...')
                else:
                  #Un-usual case, log everything to level 1 and continue:
                  logger.error('PROBLEM: negative energy value. initialWsReading='+str(initialWsReading) + '; previousWsReading=' + str(previousWsReading) + '; currentWsReading='+str(currentWsReading)+'; logEnergyState='+str(logEnergyState)+'; sessionEndedAt='+str(sessionEndedAt)+'; state2Timestamp='+str(state2Timestamp)+'; sessionStartAt='+str(sessionStartAt)+';')
                  logEnergyState = 0
        previousWsReading = currentWsReading

def on_publish(mosq, obj, mid):
    print("mid: "+str(mid))


def on_subscribe(mosq, obj, mid, granted_qos):
    logger.info("Subscribed: "+str(mid)+" "+str(granted_qos))


def on_log(mosq, obj, level, string):
    print(string)


def on_disconnect(client, userdata, rc):
    if rc != 0:
        logger.critical("Unexpected disconnection rc="+str(rc))


# --- MAIN -----------------------------------------------------

logger = logging.getLogger('OpenEVSE_mqtt_parser')
logger.setLevel(logging.INFO)

# create file handler which logs even debug messages
handler = logging.handlers.RotatingFileHandler(LOG_FILENAME, maxBytes=1048576, backupCount=10)

# create formatter and add it to the handlers
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
handler.setFormatter(formatter)

logger.addHandler(handler)

logger.info('EVSE logger - v'+str(M_VERSION)+' - M level is: '+str(M_INFO))

mainloop = 1
while mainloop == 1:
    mqttc = mqtt.Client()
    mqttc.on_connect = on_connect
    mqttc.on_message = on_message
    mqttc.on_subscribe = on_subscribe
    mqttc.on_disconnect = on_disconnect

    rc = 1
    while rc == 1:
        try:
            mqttc.connect(MQTT_Broker, int(MQTT_Port),
                          int(Keep_Alive_Interval))
            mqttc.subscribe(MQTT_Topics)
            rc = 0
        except:
            logger.warn("Warning: No broker found. Retry in one minute.")
            time.sleep(60)
            pass
    
    while rc == 0:
        try:
            rc = mqttc.loop()
        except:
            rc = 1
            time.sleep(5)

    logger.warn("Warning: Connection error - Restarting.")

    if M_INFO > 2:
        mainloop = 0

mqttc.disconnect()
logger.critical("End of program - Disconnected, done.")
