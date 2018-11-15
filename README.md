# EV car charging session logger

The short description:

 This project is about capturing EV charging sessions connected with OpenEVSE, log data in MySQL and list sessions in a convenient web page.

The longer story:

 This project contains scripts for 1) capturing and processing data sent from OpenEVSE Wifi module to a MQTT service (Mosquitto), and 2) php script for presentation.
 The Python script is used as a client/listener for MQTT topics to log charging sessions. As part of the configuration, OpenEVSE must publish data to MQTT. The biggest part is to run the Python script as a listener and detecting the different states of the EV charging sessions. OpenEVSE Wifi regularly sends topics to MQTT (when configured on the Wifi end). The Python script subscribe to 2 of those: Wh (ie. energy transfered), and state (the charging status). What I discovered is about the concept of "session" for which, as long as the car is plugged, might draw energy from without resetting the energy counter. I named this as "delta session" in the python script. At end of each cycle, any situation where state goes from state 3 down to 2 or 1 (not higher, which is not considered), computes and logs an insert into MySQL. For each cycle logged data consists of end timestamp, energy (in WattSecond), duration, and few more (fixed ones). With such, the web-page lists all sessions along with totals (where applies).


## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

The setup requires:
 * OpenEVSE Wifi module
 * MQTT (Mosquito)
 * MySQL (I'm repurposing MariaDB from EmonCMS)
 * and a web-server (I'm repurposing the Apache web-server running EmonCMS)

Also required for Python:
 * MySQL-connection library module
 * Paho-mqtt library module


### Installing

As long as you have MQTT setup and OpenEVSE Wifi module publishing data to it, you're pretty good to go with this project.

Well, pretty simple and straightforward... ensure python has the required libraries/modules. Log into MySQL and create the database. Personnally, I use "mqtt" and the table "energySession", respectively. In MySQL ensure a user/password with grants to (at least) "insert" data into mqtt database (I used "ALL").

The python script, edit MQTT and MySQL host, credentials, etc to reflect your environment.

Run the python script, the script runs like a service. At the moment of documeting this, I run the script in a screen session with logging outputs to file (ie, screenlogs.0). This way the script runs without me to be logged in.


## Running the tests

To ensure the python logger script runs correctly, check its output to console. The logging level is set to 2 in the script and will output each MQTT message received, along with detected state and charging session.


## Deployment

I haven't figured yet if and how the python script should be better strethen to run as a service and recover from an issue like MQTT lost of connection and/or MySQL unavailability... although the script has some code to handle those situations. In other words, I do not presume this is the final and fully serviceable script. To be improved.


## Contributing

Thanks to the wonderful team around OpenEVSE.
Thanks to developpers of Paho-mqtt python client.
Thanks to Energy Monitor (EmonCMS) project team.

## Versioning

I consider this as an initial version.

## Authors

* **Yanick Heynemand** - *Initial work*

See also the list of [contributors](https://github.com/yheynema/openevse-mqtt-mysql/graphs/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* (will update soon)

