* **To mark machine is issued --->**  UPDATE patients SET hearing\_aid\_issued = 1 WHERE op\_number = 'OP-110';
* **Reset Machine to NOT Issued --->** UPDATE patients SET hearing\_aid\_issued = 0 WHERE op\_number = 'OP-103';
* **To delete an already done reimbursement --->** DELETE FROM reimbursements WHERE op\_number = OP-101;
* **To clear the reimbursement records and start from beginning --->** TRUNCATE TABLE reimbursements;

&#x09;							    TRUNCATE TABLE special\_requests;

* 

