data = read.csv('level2/level2_ normalized_all_Bangkok Metropolis.csv ', header = FALSE);
x = data[,2]
y =data[,1]
grid2=data.frame(x)
knn10 = FNN::knn.reg(train = x, test = grid2, y = y, k = 4)

plot(x,y)
ORD = order(grid2$x)
lines(grid2$x[ORD],knn10$pred[ORD])
knn10$pred[1]
grid2$x[1]

