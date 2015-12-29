<?php

class BlogMgr
{
	const PASSWD = '1234';	// todo: should be encrypted
	private $oDb = null;

	public function setBlog($sComment, $sWriter, $sPasswd) {
		if($sPasswd != self::PASSWD) return false;
		$nTime = time();
		if($sWriter == '') $sWriter='Administrator';

		$this->openDb();
		$oStmt = $this->oDb->prepare('
			INSERT INTO "main"."blog" ("date","comment","writer")
			VALUES (:date, :comment, :writer);'
		);
		$oStmt->bindParam('date', $nTime);
		$oStmt->bindParam('comment', $sComment);
		$oStmt->bindParam('writer', $sWriter);
		$oStmt->execute();
		$this->closeDb();
		return true;
	}

	public function getAllBlogs() {
		$this->openDb();
		$oStmt = $this->oDb->prepare('SELECT * FROM blog ORDER BY date DESC');
		$oStmt->execute();
		$oResult = $oStmt->fetchAll(PDO::FETCH_OBJ);
		$this->closeDb();
		foreach($oResult as $blog) {
			$this->convertTimeUnixToStr($blog);
			$this->convertSpecialChars($blog);
		}
		return $oResult;
	}

	private function convertTimeUnixToStr ($oBlog) {
		$zone = 3600*-5;	//USA
		$oBlog->date = gmdate('M d Y H:i', $oBlog->date + $zone);
	}

	private function convertSpecialChars ($oBlog) {
		$oBlog->comment = nl2br(htmlspecialchars($oBlog->comment));
		$oBlog->writer = nl2br(htmlspecialchars($oBlog->writer));
	}

	private function openDb() {
		$this->oDb = new PDO('sqlite:' . __DIR__ . '/../db/blog.sqlite');
	}

	private function closeDb() {
		$this->oDb = null;
	}


}
