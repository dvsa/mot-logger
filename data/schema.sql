USE `dvsa_logger`;

CREATE TABLE `api_client_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `endpoint_uri` varchar(255) DEFAULT NULL,
  `request_method` varchar(45) DEFAULT NULL,
  `parameters` text,
  `timestamp` timestamp NULL DEFAULT NULL,
  `priority` varchar(45) DEFAULT NULL,
  `priorityName` varchar(45) DEFAULT NULL,
  `message` text,
  `request_uuid` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
COMMENT 'Logs the requests made by the API client in the Web Frontend.';

--
-- Table structure for table `api_request`
--

CREATE TABLE `api_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT NULL,
  `priority` varchar(45) DEFAULT NULL,
  `priorityName` varchar(45) DEFAULT NULL,
  `message` text,
  `uri` varchar(255) DEFAULT NULL,
  `request_method` varchar(45) DEFAULT NULL,
  `parameters` text,
  `api_request_uuid` varchar(45) DEFAULT NULL,
  `frontend_request_uuid` varchar(45) DEFAULT NULL,
  `openam_token` varchar(255) DEFAULT NULL,
  `memory_usage` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_request_uuid` (`api_request_uuid`),
  KEY `frontend_request_uuid` (`frontend_request_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
COMMENT 'Logs a request made to the API tier.';

--
-- Table structure for table `doctrine_query`
--

CREATE TABLE `doctrine_query` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT NULL,
  `priority` varchar(45) DEFAULT NULL,
  `priorityName` varchar(45) DEFAULT NULL,
  `message` text,
  `api_request_uuid` varchar(45) DEFAULT NULL,
  `parameters` text,
  `context` text,
  `query` text,
  `types` text,
  `query_time` FLOAT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
COMMENT 'Logs an SQL query performed by Doctrine';

--
-- Table structure for table `frontend_request`
--

CREATE TABLE `frontend_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT NULL,
  `priority` varchar(45) DEFAULT NULL,
  `priorityName` varchar(45) DEFAULT NULL,
  `message` text,
  `username` varchar(45) DEFAULT NULL,
  `uri` varchar(255) DEFAULT NULL,
  `request_method` varchar(45) DEFAULT NULL,
  `route` varchar(45) DEFAULT NULL,
  `parameters` text,
  `openam_token` varchar(255) DEFAULT NULL,
  `request_uuid` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `php_session_id` varchar(45) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `memory_usage` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_uuid` (`request_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
COMMENT 'Logs a request made to the web frontend.';

--
-- Table structure for table `api_response`
--

CREATE TABLE `api_response` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `timestamp` TIMESTAMP NULL,
  `priority` VARCHAR(45) NULL,
  `priorityName` VARCHAR(45) NULL,
  `message` TEXT NULL,
  `status_code` VARCHAR(45) NULL,
  `content_type` VARCHAR(45) NULL,
  `response_content` TEXT NULL,
  `frontend_request_uuid` VARCHAR(45) NULL,
  `api_request_uuid` VARCHAR(45) NULL,
  `openam_token` VARCHAR(255) NULL,
  `execution_time` FLOAT NULL,
  PRIMARY KEY (`id`),
  INDEX `api_request_uuid` (`api_request_uuid` ASC),
  INDEX `frontend_request_uuid` (`frontend_request_uuid` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT 'Logs a response from the API.';