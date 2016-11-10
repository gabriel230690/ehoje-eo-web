SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `eonline` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
USE `eonline` ;

-- -----------------------------------------------------
-- Table `eonline`.`usuario`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `eonline`.`usuario` (
  `idUsuario` INT NOT NULL AUTO_INCREMENT ,
  `dsNome` VARCHAR(100) NOT NULL ,
  `dsEmail` VARCHAR(50) NULL ,
  `nrDDD` INT(3) NULL ,
  `nrTelefone` INT(10) NULL ,
  `dtUltAcesso` DATETIME NULL ,
  `flgAdministra` INT(1) NOT NULL ,
  PRIMARY KEY (`idUsuario`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `eonline`.`conversa`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `eonline`.`conversa` (
  `idConversa` INT NOT NULL AUTO_INCREMENT ,
  `idUsuario` INT NOT NULL ,
  `dsAssunto` VARCHAR(45) NULL ,
  `dtCriacao` DATETIME NULL ,
  `dtUltMensagem` DATETIME NULL ,
  `idUltUsuario` INT NOT NULL ,
  `qtMensagens` INT NULL DEFAULT 1 ,
  PRIMARY KEY (`idConversa`) ,
  INDEX `fk_usuario` (`idUsuario` ASC) ,
  INDEX `fk_ultusuario` (`idUltUsuario` ASC) ,
  CONSTRAINT `fk_usuario`
    FOREIGN KEY (`idUsuario` )
    REFERENCES `eonline`.`usuario` (`idUsuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ultusuario`
    FOREIGN KEY (`idUltUsuario` )
    REFERENCES `eonline`.`usuario` (`idUsuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `eonline`.`mensagem`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `eonline`.`mensagem` (
  `idMensagem` INT NOT NULL AUTO_INCREMENT ,
  `idConversa` INT NOT NULL ,
  `idUsuario` INT NOT NULL ,
  `dtMensagem` DATETIME NULL ,
  `dsMensagem` VARCHAR(5000) NULL ,
  PRIMARY KEY (`idMensagem`) ,
  INDEX `fk_conversa` (`idConversa` ASC) ,
  INDEX `fk_usuario_rem` (`idUsuario` ASC) ,
  CONSTRAINT `fk_conversa`
    FOREIGN KEY (`idConversa` )
    REFERENCES `eonline`.`conversa` (`idConversa` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_rem`
    FOREIGN KEY (`idUsuario` )
    REFERENCES `eonline`.`usuario` (`idUsuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `eonline`.`conversa_lida`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `eonline`.`conversa_lida` (
  `idLida` INT NOT NULL AUTO_INCREMENT ,
  `idUsuario` INT NOT NULL ,
  `idConversa` INT NOT NULL ,
  `qtMensagensLidas` INT NULL ,
  PRIMARY KEY (`idLida`) ,
  INDEX `fk_usuario_lida` (`idUsuario` ASC) ,
  INDEX `fk_conversa_lida` (`idConversa` ASC) ,
  CONSTRAINT `fk_usuario_lida`
    FOREIGN KEY (`idUsuario` )
    REFERENCES `eonline`.`usuario` (`idUsuario` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_conversa_lida`
    FOREIGN KEY (`idConversa` )
    REFERENCES `eonline`.`conversa` (`idConversa` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
