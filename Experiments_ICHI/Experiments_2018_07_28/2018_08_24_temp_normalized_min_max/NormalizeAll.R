normalizeAll <- function()
{
  source('experiment_functions.R');
  files <- list.files(pattern = "all_*.csv$");
  
  for (i in files)
  {
     data = normalizeData(i);
     
  }
}


normalizeAll2 <- function()
{
  source('experiment_functions.R');
  files <- list.files(pattern = "all_*.csv$");
  
  for (i in files)
  {
    data = normalizeData2(i);
    
  }
}

totalCases <- function()
{
  files <- list.files(pattern = "^all");
  print(files);
  tot <- 0;
  for (i in files)
  {
    data = read.csv(i, header = FALSE);
    tot <- tot+sum(data[,1]);
  }
  
  print(tot);
}


