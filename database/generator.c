#include<stdio.h>

int main (){
	FILE *out = NULL;
	out = fopen("data.sql","w");

	//create a query to insert some data
	fprintf(out,"INSERT INTO student VALUES\n");

	//The first id number starts from 2000. And the ID is increased every round in the loop, to make sure it is unique.
	int i_id=2000;
	for(int r=0;r<2000;r++){
		//Create an ID number. 
		fprintf(out,"(");
		fprintf(out,"%d,",i_id+r);

		//Generate a string of length at most 20. The first letter is in capital.
		fprintf(out,"'");
		fprintf(out,"%c",rand()%26+'A');
		for(int l=rand()%19;l>0;l--){
			fprintf(out,"%c",rand()%26+'a');
		}
		fprintf(out,"',");

		//Generate an integer from 1 to 4.
		fprintf(out,"%d,",(rand()%4)+1);

		//Generate a float from 0.00 to 4.00
		float gpa=((float)(rand()%401))/100;
		fprintf(out,"%.2f,",gpa);

		//Generate an integer from 1001 to 1004, which is the program ID.
		fprintf(out,"%d",(rand()%4)+1001);
		fprintf(out,")");

		//Put a comma after each table row.
		if(r<1999)
			fprintf(out,",\n");
	}
	//The last table row does not need a comma. And the SQL ends by a semicolon.
	fprintf(out,";");
	return 0;
}