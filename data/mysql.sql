INSERT INTO `test`.`student` SET id=1 name='nine' age=20 class=1 
UPDATE `test`.`student` id=1 name='nine' age=20 class=1 SET id=1 name='nine' age=18 class=1 
INSERT INTO `test`.`student` SET id=2 name='seven' age=28 class=1 
UPDATE `test`.`student` id=2 name='seven' age=28 class=1 SET id=2 name='seven' age=28 class=2 
INSERT INTO `student` (`id`, `name`, `age`, `class`) VALUES (NULL, 'eight', '22', '3')
UPDATE `student` SET `class` = '4' WHERE `id` = '3'
INSERT INTO `student` (`id`, `name`, `age`, `class`) VALUES (NULL, 'three', '11', '22')
