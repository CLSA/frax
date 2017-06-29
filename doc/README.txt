Processing BMD and ancillary data for computing FRAX scores

1 - create the Input.txt file according to INTEGRATION_BLACKBOX.txt
2 - on Linux, place blackbox.exe and msvbm60.dll in a folder,
install wine and run "wine blackbox.exe"
3 - read Output.txt for results according to INTEGRATION_BLACKBOX.txt

For APEX generated reports

1 - in APEX, Utilities->System Configuration->Report, check
Enable FRAX, then Configure select "Use IOF configurations"
2 - under System->Ethnicity, select Default White, then Map
Country Code and set all to Canada
3 - fill in the patient biography for the hip scan of interest
4 - select Scans from the RHS button bar, under Analyzed Scans,
select the hip scan of interest, click Scan Details and fill out
the FRAX radio check items and select Canada for Country Code,
the check Force FRAX
5 - click the Report button, select the hip scan of interest, Next,
Preview.  APEX will generate the Input.txt file in C:\QDR and
blackbox.exe will generate Output.txt
