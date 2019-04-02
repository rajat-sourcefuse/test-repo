CREATE TABLE IF NOT EXISTS `execution` (
  `workflow_id` int(10) unsigned NOT NULL,
  `execution_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `execution_parent` int(10) unsigned DEFAULT NULL,
  `execution_started` int(11) NOT NULL,
  `execution_suspended` int(11) DEFAULT NULL,
  `execution_variables` blob,
  `execution_waiting_for` blob,
  `execution_threads` blob,
  `execution_next_thread_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`execution_id`,`workflow_id`),
  KEY `execution_parent` (`execution_parent`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;
-- --------------------------------------------------------
--
-- Table structure for table `execution_state`
--
CREATE TABLE IF NOT EXISTS `execution_state` (
  `execution_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `node_state` blob,
  `node_activated_from` blob,
  `node_thread_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`execution_id`,`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------
--
-- Table structure for table `node`
--
CREATE TABLE IF NOT EXISTS `node` (
  `workflow_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node_class` varchar(255) NOT NULL,
  `node_configuration` blob,
  PRIMARY KEY (`node_id`),
  KEY `workflow_id` (`workflow_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;
--
-- Dumping data for table `node`
--
INSERT INTO `node` (`workflow_id`, `node_id`, `node_class`, `node_configuration`) VALUES
(1, 1, 'ezcWorkflowNodeStart', ''),
(1, 2, 'ezcWorkflowNodeEnd', ''),
(1, 3, 'ezcWorkflowNodeAction', 0x613a323a7b733a353a22636c617373223b733a31343a2261736b466f72526573706f6e7365223b733a393a22617267756d656e7473223b613a303a7b7d7d);
-- --------------------------------------------------------
--
-- Table structure for table `node_connection`
--
CREATE TABLE IF NOT EXISTS `node_connection` (
  `node_connection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `incoming_node_id` int(10) unsigned NOT NULL,
  `outgoing_node_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`node_connection_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;
--
-- Dumping data for table `node_connection`
--
INSERT INTO `node_connection` (`node_connection_id`, `incoming_node_id`, `outgoing_node_id`) VALUES
(1, 1, 3),
(2, 3, 2),
(3, 4, 6),
(4, 6, 5);
-- --------------------------------------------------------
--
-- Table structure for table `variable_handler`
--
CREATE TABLE IF NOT EXISTS `variable_handler` (
  `workflow_id` int(10) unsigned NOT NULL,
  `variable` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`workflow_id`,`class`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------
--
-- Table structure for table `workflow`
--
CREATE TABLE IF NOT EXISTS `workflow` (
  `workflow_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_name` varchar(255) NOT NULL,
  `workflow_version` int(10) unsigned NOT NULL DEFAULT '1',
  `workflow_created` int(11) NOT NULL,
  PRIMARY KEY (`workflow_id`),
  UNIQUE KEY `name_version` (`workflow_name`,`workflow_version`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;
