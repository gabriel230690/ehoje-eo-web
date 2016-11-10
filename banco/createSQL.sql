SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `ehojeapp2` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
USE `ehojeapp2` ;

-- -----------------------------------------------------
-- Table `ehojeapp2`.`site`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`site` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`site` (
  `idSite` INT NOT NULL AUTO_INCREMENT ,
  `dsSite` VARCHAR(45) NULL ,
  PRIMARY KEY (`idSite`) ,
  UNIQUE INDEX `dsSite_UNIQUE` (`dsSite` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`pais`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`pais` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`pais` (
  `idPais` INT NOT NULL AUTO_INCREMENT ,
  `nmPais` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`idPais`) ,
  UNIQUE INDEX `nmPais_UNIQUE` (`nmPais` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`estado`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`estado` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`estado` (
  `idEstado` INT NOT NULL AUTO_INCREMENT ,
  `nmEstado` VARCHAR(45) NOT NULL ,
  `idPais` INT NOT NULL ,
  PRIMARY KEY (`idEstado`) ,
  INDEX `fk_estado_pais1` (`idPais` ASC) ,
  UNIQUE INDEX `nmEstado_UNIQUE` (`nmEstado` ASC) ,
  CONSTRAINT `fk_estado_pais1`
    FOREIGN KEY (`idPais` )
    REFERENCES `ehojeapp2`.`pais` (`idPais` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`cidade`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`cidade` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`cidade` (
  `idCidade` INT NOT NULL AUTO_INCREMENT ,
  `nmCidade` VARCHAR(45) NOT NULL ,
  `idEstado` INT NOT NULL ,
  `idPais` INT NOT NULL ,
  `latitude` DOUBLE NULL ,
  `longitude` DOUBLE NULL ,
  PRIMARY KEY (`idCidade`) ,
  INDEX `fk_cidade_estado1` (`idEstado` ASC) ,
  INDEX `fk_cidade_pais1` (`idPais` ASC) ,
  UNIQUE INDEX `nmCidade_UNIQUE` (`nmCidade` ASC) ,
  CONSTRAINT `fk_cidade_estado1`
    FOREIGN KEY (`idEstado` )
    REFERENCES `ehojeapp2`.`estado` (`idEstado` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_cidade_pais1`
    FOREIGN KEY (`idPais` )
    REFERENCES `ehojeapp2`.`pais` (`idPais` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`local`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`local` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`local` (
  `idLocal` INT NOT NULL AUTO_INCREMENT ,
  `dsLocal` VARCHAR(70) CHARACTER SET 'latin1' COLLATE 'latin1_swedish_ci' NULL ,
  `dsEndereco` VARCHAR(70) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL ,
  `dsTelefone` VARCHAR(200) NULL ,
  `dsSite` VARCHAR(500) NULL ,
  `id_instagram` INT NULL DEFAULT 0 ,
  `id_fanpage` BIGINT(20) NULL DEFAULT 0 ,
  `dtAtualizacao` DATETIME NULL ,
  `id_foursquare` VARCHAR(100) NULL ,
  `dsImgCover` VARCHAR(400) NULL ,
  `dsImgProfile` VARCHAR(400) NULL ,
  `dsAbertura` VARCHAR(400) NULL ,
  `nrLatitude` FLOAT NULL ,
  `nrLongitude` FLOAT NULL ,
  `idCidade` INT NOT NULL ,
  PRIMARY KEY (`idLocal`) ,
  INDEX `fk_cidade` (`idCidade` ASC) ,
  INDEX `in_dsLocal` (`dsLocal` ASC) ,
  INDEX `in_fanpage` (`id_fanpage` ASC) ,
  CONSTRAINT `fk_cidade`
    FOREIGN KEY (`idCidade` )
    REFERENCES `ehojeapp2`.`cidade` (`idCidade` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`evento`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`evento` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`evento` (
  `idEvento` INT(11) NOT NULL AUTO_INCREMENT ,
  `dtEvento` DATE NOT NULL ,
  `hrEvento` TIME NULL ,
  `dsLink` VARCHAR(254) CHARACTER SET 'latin1' COLLATE 'latin1_swedish_ci' NULL ,
  `dsAtracao` VARCHAR(245) CHARACTER SET 'latin1' COLLATE 'latin1_swedish_ci' NOT NULL ,
  `dtCriacao` DATE NOT NULL ,
  `hrCriacao` TIME NOT NULL ,
  `qtConfirmados` INT NULL DEFAULT 0 ,
  `qtHomens` INT NULL DEFAULT 0 ,
  `qtMulheres` INT NULL DEFAULT 0 ,
  `pcHomens` INT NULL DEFAULT 0 ,
  `pcMulheres` INT NULL DEFAULT 0 ,
  `dsImgCover` VARCHAR(400) NULL ,
  `idFacebook` BIGINT NULL DEFAULT 0 ,
  `idPrioridade` INT NULL DEFAULT 0 ,
  `idSite` INT NOT NULL ,
  `idLocal` INT NOT NULL ,
  PRIMARY KEY (`idEvento`) ,
  INDEX `fk_evento_site1` (`idSite` ASC) ,
  INDEX `fk_evento_local1` (`idLocal` ASC) ,
  INDEX `in_data` (`dtEvento` ASC) ,
  UNIQUE INDEX `uq_local_data` (`dtEvento` ASC, `idLocal` ASC) ,
  CONSTRAINT `fk_evento_site1`
    FOREIGN KEY (`idSite` )
    REFERENCES `ehojeapp2`.`site` (`idSite` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_evento_local1`
    FOREIGN KEY (`idLocal` )
    REFERENCES `ehojeapp2`.`local` (`idLocal` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`distancia`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`distancia` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`distancia` (
  `idCidOrigem` INT NOT NULL ,
  `idCidDestino` INT NOT NULL ,
  `nrDistancia` DOUBLE NOT NULL ,
  INDEX `fk_distancia_cidade1` (`idCidOrigem` ASC) ,
  PRIMARY KEY (`idCidDestino`, `idCidOrigem`) ,
  CONSTRAINT `fk_distancia_cidade1`
    FOREIGN KEY (`idCidOrigem` )
    REFERENCES `ehojeapp2`.`cidade` (`idCidade` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_distancia_cidade2`
    FOREIGN KEY (`idCidDestino` )
    REFERENCES `ehojeapp2`.`cidade` (`idCidade` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`nome_local`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`nome_local` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`nome_local` (
  `idNomeLocal` INT NOT NULL AUTO_INCREMENT ,
  `dsNome` VARCHAR(70) NULL ,
  `idLocal` INT NOT NULL ,
  PRIMARY KEY (`idNomeLocal`) ,
  INDEX `fk_local` (`idLocal` ASC) ,
  UNIQUE INDEX `in_dsNome` (`dsNome` ASC) ,
  CONSTRAINT `fk_local`
    FOREIGN KEY (`idLocal` )
    REFERENCES `ehojeapp2`.`local` (`idLocal` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`ignora_local`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`ignora_local` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`ignora_local` (
  `idLocal` INT NOT NULL AUTO_INCREMENT ,
  `dsLocal` VARCHAR(70) NULL ,
  PRIMARY KEY (`idLocal`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`log_gravacao`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`log_gravacao` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`log_gravacao` (
  `idLog` INT NOT NULL AUTO_INCREMENT ,
  `dtLog` DATE NOT NULL ,
  `hrLog` TIME NOT NULL ,
  `idSite` INT NOT NULL ,
  `dsError` VARCHAR(200) NOT NULL DEFAULT 1 ,
  PRIMARY KEY (`idLog`) ,
  INDEX `fk_site` (`idSite` ASC) ,
  UNIQUE INDEX `uk_error` (`dsError` ASC) ,
  CONSTRAINT `fk_site`
    FOREIGN KEY (`idSite` )
    REFERENCES `ehojeapp2`.`site` (`idSite` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`participante`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`participante` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`participante` (
  `idParticipante` BIGINT NOT NULL COMMENT 'ID do perfil no Facebook' ,
  `dsNome` VARCHAR(45) NOT NULL ,
  `dsSexo` VARCHAR(1) NOT NULL ,
  PRIMARY KEY (`idParticipante`) ,
  UNIQUE INDEX `uk_facebook` (`idParticipante` ASC) ,
  INDEX `in_nome` (`dsNome` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`evento_participante`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`evento_participante` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`evento_participante` (
  `idEvento` INT NOT NULL ,
  `idParticipante` BIGINT NOT NULL ,
  PRIMARY KEY (`idEvento`, `idParticipante`) ,
  INDEX `fk_evento` (`idEvento` ASC) ,
  INDEX `fk_participante` (`idParticipante` ASC) ,
  CONSTRAINT `fk_evento`
    FOREIGN KEY (`idEvento` )
    REFERENCES `ehojeapp2`.`evento` (`idEvento` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_participante`
    FOREIGN KEY (`idParticipante` )
    REFERENCES `ehojeapp2`.`participante` (`idParticipante` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`busca`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`busca` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`busca` (
  `idBusca` INT NOT NULL AUTO_INCREMENT ,
  `dtBusca` DATE NOT NULL ,
  `nmCidade` VARCHAR(45) NOT NULL ,
  `qtBuscas` INT NOT NULL ,
  PRIMARY KEY (`idBusca`) ,
  UNIQUE INDEX `in_busca` (`dtBusca` ASC, `nmCidade` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`sugestao_local`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`sugestao_local` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`sugestao_local` (
  `idLocal` INT NOT NULL AUTO_INCREMENT ,
  `dsLocal` VARCHAR(70) NULL ,
  `idCidade` INT NOT NULL ,
  `id_fanpage` BIGINT(20) NULL ,
  `id_foursquare` VARCHAR(100) NULL ,
  `qtFaceLikes` INT NULL ,
  PRIMARY KEY (`idLocal`) ,
  UNIQUE INDEX `uq_local` (`idCidade` ASC, `dsLocal` ASC) ,
  INDEX `fk_cidade` (`idCidade` ASC) ,
  CONSTRAINT `fk_cidade`
    FOREIGN KEY (`idCidade` )
    REFERENCES `ehojeapp2`.`cidade` (`idCidade` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ehojeapp2`.`cron`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ehojeapp2`.`cron` ;

CREATE  TABLE IF NOT EXISTS `ehojeapp2`.`cron` (
  `idCron` INT NOT NULL AUTO_INCREMENT ,
  `dsPrograma` VARCHAR(45) NOT NULL ,
  `dtExecucao` DATETIME NULL ,
  PRIMARY KEY (`idCron`) ,
  INDEX `uq_programa` (`dsPrograma` ASC) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
