separate<- function()
{
  data_files = list.files('.', pattern = '^inci');
  temp = read.csv(data_files[1]);
  nameList = unique(temp[,2]);

  for(dist in nameList)
  { 
    districtData = matrix(0,0,0);
  for (f in data_files)
  {
     print(f);
     data = read.csv(f);
     districtData <- rbind(districtData, subset(data, district==dist));
    
  }
    write.table(districtData, paste('full_',dist,'.csv'), col.names = FALSE, row.names = FALSE, sep = ',');
  }
}

getRainTempAndCase<-function()
{
  data_files = list.files('.', pattern = '^full_');
  for (f in data_files)
  {
    data = read.csv(f, header = FALSE);
    data2 = data[,3:5];
    write.table(data2, paste('all_',f), col.names = FALSE, row.names = FALSE, sep = ',');
    
  }
  
}