ALTER TABLE `user` 
  ADD COLUMN `disableSearchProfileChanging` BOOLEAN;

ALTER TABLE `tenant` 
  ADD COLUMN `disableSearchInOtherTenants` BOOLEAN;
  