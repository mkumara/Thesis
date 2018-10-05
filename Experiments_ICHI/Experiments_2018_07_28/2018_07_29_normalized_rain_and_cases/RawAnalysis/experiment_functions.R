normalizeData <- function(filename) {
  data = read.csv(filename, header = FALSE);
  matrixData = as.matrix(data);
  size = dim(matrixData);
  normalizedMatrix = matrix(0,0,size[2]);
  
 for (i in seq(1,size[1], 12))
 {
   temp <- matrixData[i:(i+11),];
   temp <- round(temp, 2);
   temp[1:12,1] = round(temp[1:12,1]/max(temp[1:12,1]), 4);
   normalizedMatrix <- rbind(normalizedMatrix, temp);
 }
  normalizedFilename = paste('normalized', filename, sep="_");
  write.table(normalizedMatrix, normalizedFilename, row.names = FALSE, col.names = FALSE, sep = ",");
  print(filename);
  print(cor(normalizedMatrix[,1], normalizedMatrix[,2]));
  return(normalizedMatrix);
}

normalizeData2 <- function(filename) {
  data = read.csv(filename, header = FALSE);
  matrixData = as.matrix(data);
  size = dim(matrixData);
  normalizedMatrix = matrix(0,0,size[2]);
  
  for (i in seq(1,size[1], 12))
  {
    temp <- matrixData[i:(i+11),];
    temp <- round(temp, 2);
    temp[1:12,1] = round(temp[1:12,1]/max(temp[1:12,1]), 4);
    temp[1:12,2] = round(temp[1:12,2]/max(temp[1:12,2]), 4);
    normalizedMatrix <- rbind(normalizedMatrix, temp);
  }
  normalizedFilename = paste('normalized', filename, sep="_");
  write.table(normalizedMatrix, normalizedFilename, row.names = FALSE, col.names = FALSE, sep = ",");
  print(filename);
  print(cor(normalizedMatrix[,1], normalizedMatrix[,2]));
  return(normalizedMatrix);
}


processAllDistricts <- function()
{
  normalized_files = list.files('.', pattern = '^norm');
  
  for (f in normalized_files)
  {
    data = read.csv(f, header = FALSE);
    
    png(filename=paste("image", f,".png",""));
    
    plot(data[,1:2], cex = 0.5)
    hpts <- chull(data[,1:2]);
    hpts <- c(hpts, hpts[1]);
    lines(data[hpts, 1:2 ]);
    
    cor1 = cor(data[,1], data[,2]);
    
    data_r = data[-(hpts),];
    r_hpts <- chull(data_r[,1:2]);
    r_hpts <- c(r_hpts, r_hpts[1]);
    lines(data_r[r_hpts, 1:2 ]);
    
    cor2 = cor(data_r[,1], data_r[,2]);
    
    dev.off();
    
    data_r2 = data_r[-(r_hpts),];
    cor3 = cor(data_r2[,1], data_r2[,2]);
   
   print(f);
   write.table(c(cor1[1], cor2[1], cor3[1]), paste('cor_',f,""));
    
  }
}


getLevels<- function()
{
  normalized_files = list.files('.', pattern = '^norm');
  
  for (f in normalized_files)
  {
    data = read.csv(f, header = FALSE);
    
    hpts <- chull(data[,1:2]);
    
    data_r = data[-(hpts),];
    r_hpts <- chull(data_r[,1:2]);
    
    data_r2 = data_r[-(r_hpts),];
    
    
    write.table(data, paste('level0/level0_',f,""), col.names = FALSE, row.names = FALSE, sep = ",");
    write.table(data_r, paste('level1/level1_',f,""), col.names = FALSE, row.names = FALSE, sep = ",");
    write.table(data_r2, paste('level2/level2_',f,""), col.names = FALSE, row.names = FALSE, sep = ",");
  }
}


consolidateCor <- function()
{
  cor_files = list.files('.', pattern = '^cor');
  final_cor = matrix(0,0,3);
  print(dim(final_cor));
  
  for (f in cor_files)
  {
    print(f);
    corFile = read.table(f);
    temp <- matrix(c(corFile[1,1], corFile[2,1], corFile[3,1]), 1, 3);
    
    print(dim(temp));
    final_cor <- rbind(final_cor, temp);
    #print(final_cor);
    
  
  }
  write.table(final_cor, 'consolidatedCor.csv', col.names = FALSE, row.names = FALSE, sep = ',');
}

#without normalizing
processAllDistrictsRaw <- function()
{
  files = list.files('.', pattern = '^all');
  
  for (f in files)
  {
    data = read.csv(f, header = FALSE);
    
   
    cor1 = cor(data[,1], data[,2]);

    write.table(cor1[1], paste('all_cor_',f,""));
    
  }
}

#without normalizing
consolidateCorRaw <- function()
{
  cor_files = list.files('.', pattern = '^all_cor');
  final_cor = matrix(0,0,3);
  
  for (f in cor_files)
  {
    corFile = read.table(f);
    temp <- matrix(c(corFile[1,1], corFile[2,1], corFile[3,1]), 1, 3);
  
    final_cor <- rbind(final_cor, temp);
    #print(final_cor);
    
    
  }
  write.table(final_cor, 'consolidatedCorRaw.csv', col.names = FALSE, row.names = FALSE, sep = ',');
}