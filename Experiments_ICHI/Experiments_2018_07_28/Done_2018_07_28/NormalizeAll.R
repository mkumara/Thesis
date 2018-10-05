normalizeAll <- function()
{
  source('experiment_functions.R');
  files <- list.files(pattern = ".csv$");
  
  for (i in files)
  {
     data = normalizeData(i);
     
  }
}